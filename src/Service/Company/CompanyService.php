<?php
// ─────────────────────────────────────────────────────────────────────────────
// FILE: CompanyService.php
// CORRECTION: getCurrentCompany() now reads X-Company-Id header from Flutter
//
// HOW IT WORKS:
//   Flutter sends X-Company-Id header with every API request.
//   This header contains the ID of the company the admin activated
//   (by double-clicking a company card in the app).
//
//   For ROLE_ADMIN with multiple companies:
//     → Read X-Company-Id header
//     → Verify the company belongs to this admin (security check)
//     → Return that company
//
//   For ROLE_MANAGER / ROLE_TELLER / ROLE_WAITER:
//     → They belong to exactly one company
//     → Return user->getCompany() (ignore the header)
//
// SECURITY:
//   We always verify ownership before returning a company.
//   An admin cannot access another admin's data by sending a fake company ID.
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Service\Company;

use App\Entity\Company\Company;
use App\Entity\Security\User;
use App\Repository\Company\CompanyRepository;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CompanyService
{
    private EntityManagerInterface $em;
    private CompanyRepository      $companyRepository;
    private Security               $security;
    private RequestStack           $requestStack; // reads current HTTP request headers

    public function __construct(
        EntityManagerInterface $em,
        CompanyRepository      $companyRepository,
        Security               $security,
        RequestStack           $requestStack  // injected by Symfony DI
    ) {
        $this->em                = $em;
        $this->companyRepository = $companyRepository;
        $this->security          = $security;
        $this->requestStack      = $requestStack;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getCurrentCompany — returns the active company for the current user
    //
    // PRIORITY:
    //   1. For ROLE_ADMIN: read X-Company-Id header sent by Flutter
    //      → verify the company belongs to this admin
    //      → return it if valid
    //   2. Fallback: return user->getCompany() for all roles
    // ─────────────────────────────────────────────────────────────────────────
    public function getCurrentCompany(): ?Company
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        // For ROLE_ADMIN: check if Flutter sent a specific company to use
        if (in_array('ROLE_ADMIN', $user->getRoles())) {

            // Read the X-Company-Id header sent by Flutter api_service.dart
            $request   = $this->requestStack->getCurrentRequest();
            $companyId = $request?->headers->get('X-Company-Id');

            if ($companyId) {
                // Find this company in the database
                $company = $this->companyRepository->find((int) $companyId);

                if ($company) {
                    // SECURITY: verify this company belongs to this admin
                    // Check if admin is the owner of this company
                    $ownerCompanies = $this->companyRepository->findBy([
                        'owner' => $user
                    ]);

                    foreach ($ownerCompanies as $owned) {
                        if ($owned->getId() === $company->getId()) {
                            // Confirmed: this admin owns this company → return it
                            return $company;
                        }
                    }
                    // Company not owned by this admin → fall through to default
                }
            }

            // No X-Company-Id header or invalid ID
            // Return the first company owned by this admin (default behavior)
            return $this->companyRepository->findOneBy(['owner' => $user]);
        }

        // For ROLE_MANAGER, ROLE_TELLER, ROLE_WAITER:
        // They belong to exactly one company — return it directly
        return $user->getCompany();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // searchC — search company by name
    // ─────────────────────────────────────────────────────────────────────────
    public function searchC(string $cname): array
    {
        $companies = $this->companyRepository->findByName($cname);
        if (!$companies) {
            throw new \InvalidArgumentException('Company not found');
        }

        $data = [];
        foreach ($companies as $company) {
            $data[] = $this->formatCompany($company);
        }
        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // listC — list all companies owned by the current admin
    // ─────────────────────────────────────────────────────────────────────────
    public function listC(): array
    {
        $user      = $this->security->getUser();
        $companies = $this->companyRepository->findBy(['owner' => $user]);

        if (!$companies) {
            throw new RuntimeException('No companies registered');
        }

        $data = [];
        foreach ($companies as $company) {
            $data[] = $this->formatCompany($company);
        }
        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // createC — create a new company for the current admin
    // ─────────────────────────────────────────────────────────────────────────
    public function createC(Request $request): array
    {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        $required = ['nameComp', 'emailComp', 'phone', 'city', 'numEmpl', 'siteWeb'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("The field $field is required");
            }
        }

        $company = new Company();
        $company->setNameComp($data['nameComp']);
        $company->setEmailComp($data['emailComp']);
        $company->setPhone($data['phone']);
        $company->setCity($data['city']);
        $company->setNumEmpl($data['numEmpl']);
        $company->setSiteWeb($data['siteWeb']);
        $company->setOwner($user); // the current admin is the owner

        $this->em->persist($company);
        $this->em->flush();

        return [$this->formatCompany($company)];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // updateC — update an existing company (owner only)
    // ─────────────────────────────────────────────────────────────────────────
    public function updateC(int $id, Request $request): array
    {
        $user    = $this->security->getUser();
        $data    = json_decode($request->getContent(), true);
        $company = $this->companyRepository->find($id);

        if (!$company) {
            throw new \InvalidArgumentException('Company not found');
        }

        if ($company->getOwner() !== $user) {
            throw new \RuntimeException('Access denied: you are not the owner');
        }

        if (isset($data['nameComp']))  $company->setNameComp($data['nameComp']);
        if (isset($data['emailComp'])) $company->setEmailComp($data['emailComp']);
        if (isset($data['phone']))     $company->setPhone($data['phone']);
        if (isset($data['city']))      $company->setCity($data['city']);
        if (isset($data['numEmpl']))   $company->setNumEmpl($data['numEmpl']);
        if (isset($data['siteWeb']))   $company->setSiteWeb($data['siteWeb']);

        $this->em->flush();

        return ['message' => 'Company updated successfully'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // deleteC — delete a company (owner only)
    // ─────────────────────────────────────────────────────────────────────────
    public function deleteC(int $id): string
    {
        $user    = $this->security->getUser();
        $company = $this->companyRepository->find($id);

        if (!$company) {
            throw new \InvalidArgumentException('Company not found');
        }

        if ($company->getOwner() !== $user) {
            throw new \RuntimeException('Access denied: you are not the owner');
        }

        $name = $company->getNameComp();
        $this->em->remove($company);
        $this->em->flush();

        return 'Company deleted successfully';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // formatCompany — helper to convert Company entity to array
    // ─────────────────────────────────────────────────────────────────────────
    private function formatCompany(Company $company): array
    {
        return [
            'id'        => $company->getId(),
            'nameComp'  => $company->getNameComp(),
            'emailComp' => $company->getEmailComp(),
            'phone'     => $company->getPhone(),
            'city'      => $company->getCity(),
            'numEmpl'   => $company->getNumEmpl(),
            'siteWeb'   => $company->getSiteWeb(),
            'owner'     => $company->getOwner()?->getUserIdentifier(),
        ];
    }
}