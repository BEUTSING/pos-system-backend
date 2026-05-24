<?php

namespace App\Service\Company;

use App\Entity\Company\Company;
use App\Entity\Security\User;
use App\Repository\Company\CompanyRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class CompanyService
{
    private EntityManagerInterface $em;
    private CompanyRepository $companyRepository;
    private LogEntryService $logEntryService;
    private Security $security;

    public function __construct(
        LogEntryService $logEntryService,
        EntityManagerInterface $em,
        CompanyRepository $companyRepository,
        Security $security
    ) {
        $this->logEntryService = $logEntryService;
        $this->em = $em;
        $this->companyRepository = $companyRepository;
        $this->security = $security;
    }
// Get the current user's company
   public function currentCompany(int $companyId): Company
{
    $user = $this->security->getUser();

    if (!$user instanceof User) {
        throw new \RuntimeException('User not authenticated');
    }

    $company = $this->companyRepository->find($companyId);

    if (!$company) {
        throw new \InvalidArgumentException('Company not found');
    }

    // Vérifie que la company choisie appartient bien au user connecté
    if ($company->getOwner() !== $user) {
        throw new \RuntimeException('Access denied: this company does not belong to you');
    }

    return $company;
}
    // Search company by name
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

    // List all companies (owned by the current user)
    public function listC(): array
    {
        $user = $this->security->getUser();
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

    // Create a new company
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
        $company->setOwner($user);

        $this->em->persist($company);
        $this->em->flush();

        $this->logEntryService->createLogEntry(
            'Company created: ' . $company->getNameComp() . ' by ' . $user->getUserIdentifier()
        );

        return [$this->formatCompany($company)];
    }

    // Update an existing company
    public function updateC(int $id, Request $request): array
    {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        $company = $this->companyRepository->find($id);
        if (!$company) {
            throw new \InvalidArgumentException('Company not found');
        }

        // Ensure the current user is the owner
        if ($company->getOwner() !== $user) {
            throw new \RuntimeException('Access denied: you are not the owner of this company');
        }

        if (isset($data['nameComp'])) {
            $company->setNameComp($data['nameComp']);
        }
        if (isset($data['emailComp'])) {
            $company->setEmailComp($data['emailComp']);
        }
        if (isset($data['phone'])) {
            $company->setPhone($data['phone']);
        }
        if (isset($data['city'])) {
            $company->setCity($data['city']);
        }
        if (isset($data['numEmpl'])) {
            $company->setNumEmpl($data['numEmpl']);
        }
        if (isset($data['siteWeb'])) {
            $company->setSiteWeb($data['siteWeb']);
        }

        $this->em->flush();

        $this->logEntryService->createLogEntry(
            'Company updated: ' . $company->getNameComp() . ' by ' . $user->getUserIdentifier()
        );

        return ['message' => 'Company updated successfully'];
    }

    // Delete a company
    public function deleteC(int $id): string
    {
        $user = $this->security->getUser();

        $company = $this->companyRepository->find($id);
        if (!$company) {
            throw new \InvalidArgumentException('Company not found');
        }

        // Ensure the current user is the owner
        if ($company->getOwner() !== $user) {
            throw new \RuntimeException('Access denied: you are not the owner of this company');
        }

        $name = $company->getNameComp();
        $this->em->remove($company);
        $this->em->flush();

        $this->logEntryService->createLogEntry(
            'Company deleted: ' . $name . ' by ' . $user->getUserIdentifier()
        );

        return 'Company deleted successfully';
    }

    // Helper: format company as array
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