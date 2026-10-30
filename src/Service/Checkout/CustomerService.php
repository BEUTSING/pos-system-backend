<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\Customer;
use App\Entity\Company\Company;
use App\Repository\Checkout\CustomerRepository;
use App\Service\Company\CompanyService;
use App\Service\Media\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\Request;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager,
        private CompanyService $companyService,
        private FileUploadService $fileUploadService,
    ) {
    }

    public function createCustomer(Request $request): array
    {
        $data = $this->validatedData($request, true);
        $customer = new Customer();
        $customer->setName($data['name']);
        $customer->setPhone($data['phone'] ?? null);
        $customer->setCity($data['city'] ?? null);
        $customer->setEmail($data['email'] ?? null);
        $customer->setAddress($data['address'] ?? null);
        $customer->setCompany($this->getCurrentCompany());

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $this->formatCustomer($customer);
    }

    public function listCustomers(): array
    {
        $customers = $this->customerRepository->findBy(
            ['company' => $this->getCurrentCompany()],
            ['id' => 'ASC'],
        );

        return array_map(fn (Customer $customer): array => $this->formatCustomer($customer), $customers);
    }

    public function searchCustomers(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new \InvalidArgumentException('Search query cannot be empty');
        }

        $customers = $this->customerRepository->createQueryBuilder('c')
            ->where('c.company = :company')
            ->andWhere('(c.name LIKE :query OR c.phone LIKE :query OR c.email LIKE :query)')
            ->setParameter('company', $this->getCurrentCompany())
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (Customer $customer): array => $this->formatCustomer($customer), $customers);
    }

    public function getCustomer(int $id): array
    {
        return $this->formatCustomer($this->findCustomerInCurrentCompany($id));
    }

    public function updateCustomer(int $id, Request $request): array
    {
        $customer = $this->findCustomerInCurrentCompany($id);
        $data = $this->validatedData($request);
        if ($data === []) {
            throw new \InvalidArgumentException('At least one customer field must be provided');
        }

        foreach (['name' => 'setName', 'phone' => 'setPhone', 'city' => 'setCity', 'email' => 'setEmail', 'address' => 'setAddress'] as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $customer->$setter($data[$field]);
            }
        }

        $this->entityManager->flush();

        return $this->formatCustomer($customer);
    }

    public function deleteCustomer(int $id): string
    {
        $customer = $this->findCustomerInCurrentCompany($id);
        $this->entityManager->remove($customer);
        $this->entityManager->flush();

        return 'Customer deleted successfully';
    }

    private function getCurrentCompany(): Company
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        return $company;
    }

    private function findCustomerInCurrentCompany(int $id): Customer
    {
        $customer = $this->customerRepository->createQueryBuilder('c')
            ->where('c.id = :id')
            ->andWhere('c.company = :company')
            ->setParameter('id', $id)
            ->setParameter('company', $this->getCurrentCompany())
            ->getQuery()
            ->getOneOrNullResult();

        if (!$customer instanceof Customer) {
            throw new \RuntimeException('Customer not found');
        }

        return $customer;
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, bool $requireName = false): array
    {
        try {
            $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Request body must contain valid JSON', 0, $exception);
        }

        if (!$data instanceof \stdClass) {
            throw new \InvalidArgumentException('Request body must be a JSON object');
        }

        $data = get_object_vars($data);
        $limits = ['name' => 100, 'phone' => 20, 'city' => 100, 'email' => 180, 'address' => 255];
        $validated = [];
        foreach ($limits as $field => $maxLength) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($field === 'name' && (!is_string($value) || trim($value) === '')) {
                throw new \InvalidArgumentException('The field name must be a non-empty string');
            }
            if ($value !== null && !is_string($value)) {
                throw new \InvalidArgumentException(sprintf('The field %s must be a string or null', $field));
            }
            if (is_string($value)) {
                $value = trim($value);
                if (iconv_strlen($value, 'UTF-8') > $maxLength) {
                    throw new \InvalidArgumentException(sprintf('The field %s must not exceed %d characters', $field, $maxLength));
                }
            }
            if ($field === 'email' && $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('The email is not valid');
            }

            $validated[$field] = $value;
        }

        if ($requireName && !array_key_exists('name', $validated)) {
            throw new \InvalidArgumentException('The field name is required');
        }

        return $validated;
    }

    /** @return array{id: int|null, name: ?string, phone: ?string, city: ?string, email: ?string} */
    private function formatCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'phone' => $customer->getPhone(),
            'city' => $customer->getCity(),
            'email' => $customer->getEmail(),
            'address' => $customer->getAddress(),
            'picture' => $this->fileUploadService->publicUrl($customer->getPicture()),
        ];
    }
}
