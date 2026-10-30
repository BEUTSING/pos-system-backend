<?php

namespace App\Service\Media;

use App\Entity\Checkout\Customer;
use App\Entity\Company\Company;
use App\Entity\Media\Logo;
use App\Repository\Checkout\CustomerRepository;
use App\Repository\Media\LogoRepository;
use App\Service\Company\CompanyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manages the Logo entity: company logos and customer pictures.
 */
class LogoService
{
    public function __construct(
        private LogoRepository $logoRepository,
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager,
        private CompanyService $companyService,
        private FileUploadService $fileUploadService,
    ) {
    }

    public function listCompanyLogos(): array
    {
        $company = $this->getCurrentCompany();
        $logos = $this->logoRepository->findForCompany($company);
        if ($logos === [] && $company->getLogo()) {
            // Legacy rows stored the logo directly on the company.
            return [[
                'id' => 0,
                'url' => $this->fileUploadService->publicUrl($company->getLogo()),
                'is_primary' => true,
            ]];
        }

        return array_map(fn (Logo $logo): array => $this->formatLogo($logo), $logos);
    }

    public function addCompanyLogo(Request $request): array
    {
        $company = $this->getCurrentCompany();
        $stored = $this->storeUpload($request, 'company');

        $logo = new Logo();
        $logo->setCompany($company);
        $logo->setPath($stored['path']);
        $logo->setIsPrimary(true);

        $this->entityManager->persist($logo);
        $company->setLogo($stored['path']);
        $this->entityManager->flush();

        return $this->formatLogo($logo);
    }

    public function deleteCompanyLogo(?int $logoId = null): void
    {
        $company = $this->getCurrentCompany();

        if ($logoId !== null) {
            $logo = $this->logoRepository->find($logoId);
            if (!$logo || $logo->getCompany()?->getId() !== $company->getId()) {
                throw new \InvalidArgumentException('Logo not found for the active company');
            }
            $this->fileUploadService->delete($logo->getPath());
            $this->entityManager->remove($logo);
            $company->setLogo(null);
            $this->entityManager->flush();

            return;
        }

        foreach ($this->logoRepository->findForCompany($company) as $logo) {
            $this->fileUploadService->delete($logo->getPath());
            $this->entityManager->remove($logo);
        }
        $this->fileUploadService->delete($company->getLogo());
        $company->setLogo(null);
        $this->entityManager->flush();
    }

    public function listCustomerLogos(int $customerId): array
    {
        $customer = $this->findCustomer($customerId);
        $logos = $this->logoRepository->findForCustomer($customer);
        if ($logos === [] && $customer->getPicture()) {
            return [[
                'id' => 0,
                'url' => $this->fileUploadService->publicUrl($customer->getPicture()),
                'is_primary' => true,
            ]];
        }

        return array_map(fn (Logo $logo): array => $this->formatLogo($logo), $logos);
    }

    public function addCustomerLogo(int $customerId, Request $request): array
    {
        $customer = $this->findCustomer($customerId);
        $stored = $this->storeUpload($request, 'customers');

        $logo = new Logo();
        $logo->setCustomer($customer);
        $logo->setPath($stored['path']);
        $logo->setIsPrimary(true);

        $this->entityManager->persist($logo);
        $customer->setPicture($stored['path']);
        $this->entityManager->flush();

        return $this->formatLogo($logo);
    }

    public function deleteCustomerImage(int $customerId, ?int $logoId = null): void
    {
        $customer = $this->findCustomer($customerId);

        if ($logoId !== null) {
            $logo = $this->logoRepository->find($logoId);
            if (!$logo || $logo->getCustomer()?->getId() !== $customer->getId()) {
                throw new \InvalidArgumentException('Image not found for this customer');
            }
            $this->fileUploadService->delete($logo->getPath());
            $this->entityManager->remove($logo);
            $customer->setPicture(null);
            $this->entityManager->flush();

            return;
        }

        foreach ($this->logoRepository->findForCustomer($customer) as $logo) {
            $this->fileUploadService->delete($logo->getPath());
            $this->entityManager->remove($logo);
        }
        $this->fileUploadService->delete($customer->getPicture());
        $customer->setPicture(null);
        $this->entityManager->flush();
    }

    private function getCurrentCompany(): Company
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        return $company;
    }

    private function findCustomer(int $customerId): Customer
    {
        $customer = $this->customerRepository->find($customerId);
        if (!$customer || $customer->getCompany()?->getId() !== $this->getCurrentCompany()->getId()) {
            throw new \RuntimeException('Customer not found');
        }

        return $customer;
    }

    /** @return array{path: string} */
    private function storeUpload(Request $request, string $folder): array
    {
        $file = $request->files->get('image') ?? $request->files->get('logo') ?? $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('An image file is required in the "image" field');
        }

        return $this->fileUploadService->store($file, $folder);
    }

    private function formatLogo(Logo $logo): array
    {
        return [
            'id' => $logo->getId(),
            'url' => $this->fileUploadService->publicUrl($logo->getPath()),
            'path' => $logo->getPath(),
            'is_primary' => $logo->isPrimary(),
        ];
    }
}
