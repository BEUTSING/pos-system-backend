<?php

namespace App\Controller\Stock;

use App\Entity\Stock\Supplier;
use App\Repository\Stock\SupplierRepository;
use App\Service\Company\CompanyService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Supplier')]
#[Route('/supplier')]
final class SupplierController extends AbstractController
{
    
    private CompanyService $companyService;

    public function __construct(
        private Security $security,
        CompanyService $companyService
    ) {
        $this->companyService = $companyService;
    }

    // ─── LIST: retrieve all suppliers of the current company ─────────────────────
    #[Route('/list', name: 'app_supplier_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_WAITER')]
    #[OA\Get(
        path: "/api/v1/supplier/list",
        summary: "List all suppliers",
        description: "Returns all suppliers belonging to the current company",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of suppliers",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",      type: "integer", example: 1),
                            new OA\Property(property: "name",    type: "string",  example: "Supplier A"),
                            new OA\Property(property: "email",   type: "string",  example: "supplierA@email.com"),
                            new OA\Property(property: "city",    type: "string",  example: "Paris"),
                            new OA\Property(property: "phone",   type: "string",  example: "+33123456789"),
                            new OA\Property(property: "company", type: "string",  example: "Acme Corp"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No suppliers found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "No suppliers found")]
                )
            )
        ]
    )]
    public function list(SupplierRepository $supplierRepository): JsonResponse
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Filter suppliers by the current company only — not findAll()
        $suppliers = $supplierRepository->findBy(['company' => $company]);
        $data = [];
        foreach ($suppliers as $supplier) {
            $data[] = [
                'id'      => $supplier->getId(),
                'name'    => $supplier->getName(),
                'email'   => $supplier->getEmail(),
                'city'    => $supplier->getCity(),
                'phone'   => $supplier->getPhone(),
                'address' => $supplier->getAddress(),
                'company' => $supplier->getCompany()?->getNameComp(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── SEARCH: search suppliers by name ───────────────────────────────────────
    #[Route('/search/{sname}', name: 'app_supplier_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/supplier/search/{sname}",
        summary: "Search suppliers by name",
        parameters: [
            new OA\Parameter(
                name: "sname",
                in: "path",
                required: true,
                description: "Supplier name to search for",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Supplier(s) found",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",      type: "integer", example: 1),
                            new OA\Property(property: "name",    type: "string",  example: "Supplier A"),
                            new OA\Property(property: "email",   type: "string",  example: "supplierA@email.com"),
                            new OA\Property(property: "city",    type: "string",  example: "Paris"),
                            new OA\Property(property: "phone",   type: "string",  example: "+33123456789"),
                            new OA\Property(property: "company", type: "string",  example: "Acme Corp"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Supplier not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Supplier not found")]
                )
            )
        ]
    )]
    public function search(SupplierRepository $supplierRepository, string $sname): JsonResponse
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json([], Response::HTTP_OK);
        }
        $suppliers = $supplierRepository->createQueryBuilder('s')
            ->andWhere('s.name LIKE :name')
            ->andWhere('s.company = :company')
            ->setParameter('name', '%' . $sname . '%')
            ->setParameter('company', $company)
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
        $data = [];
        foreach ($suppliers as $supplier) {
            $data[] = [
                'id'      => $supplier->getId(),
                'name'    => $supplier->getName(),
                'email'   => $supplier->getEmail(),
                'city'    => $supplier->getCity(),
                'phone'   => $supplier->getPhone(),
                'address' => $supplier->getAddress(),
                'company' => $supplier->getCompany()?->getNameComp(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── CREATE: create a new supplier and assign it to the current company ──────
    #[Route('/create', name: 'app_supplier_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/supplier/create",
        summary: "Create a new supplier",
        description: "Creates a new supplier and automatically assigns it to the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name",  type: "string", example: "Supplier A"),
                    new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                    new OA\Property(property: "city",  type: "string", example: "Paris"),
                    new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Supplier created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",      type: "integer", example: 1),
                        new OA\Property(property: "name",    type: "string",  example: "Supplier A"),
                        new OA\Property(property: "email",   type: "string",  example: "supplierA@email.com"),
                        new OA\Property(property: "city",    type: "string",  example: "Paris"),
                        new OA\Property(property: "phone",   type: "string",  example: "+33123456789"),
                        new OA\Property(property: "company", type: "string",  example: "Acme Corp"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - missing fields",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Missing required fields")]
                )
            )
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '') {
            return $this->json(['error' => 'The field name is required'], Response::HTTP_BAD_REQUEST);
        }
        foreach (['email', 'city', 'phone', 'address'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                return $this->json(['error' => sprintf('The field %s must be a string', $field)], Response::HTTP_BAD_REQUEST);
            }
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'The email is not valid'], Response::HTTP_BAD_REQUEST);
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        $supplier = new Supplier();
        $supplier->setName(trim($data['name']));
        $supplier->setEmail($data['email'] ?? null);
        $supplier->setCity($data['city'] ?? null);
        $supplier->setPhone($data['phone'] ?? null);
        $supplier->setAddress($data['address'] ?? null);
        $supplier->setCompany($company); // ← automatically assigned from the current session

        $em->persist($supplier);
        $em->flush();

        // $this->logEntryService->createLogEntry('Supplier created: ' . $supplier->getName());

        return $this->json([
            'id'      => $supplier->getId(),
            'name'    => $supplier->getName(),
            'email'   => $supplier->getEmail(),
            'city'    => $supplier->getCity(),
            'phone'   => $supplier->getPhone(),
            'address' => $supplier->getAddress(),
            'company' => $company->getNameComp(),
        ], Response::HTTP_CREATED);
    }

    // ─── UPDATE: update an existing supplier ────────────────────────────────────
    #[Route('/modify/{id}', name: 'app_supplier_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
        path: "/api/v1/supplier/modify/{id}",
        summary: "Update a supplier",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the supplier to update",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name",  type: "string", example: "Updated Supplier"),
                    new OA\Property(property: "email", type: "string", example: "updated@email.com"),
                    new OA\Property(property: "city",  type: "string", example: "Lyon"),
                    new OA\Property(property: "phone", type: "string", example: "+33498765432"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Supplier updated successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Supplier updated successfully")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Supplier not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Supplier not found")]
                )
            )
        ]
    )]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $company = $this->companyService->getCurrentCompany();
        $supplier = $company ? $em->getRepository(Supplier::class)->findOneBy(['id' => $id, 'company' => $company]) : null;
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }
        foreach (['name', 'email', 'city', 'phone', 'address'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && !is_string($data[$field])) {
                return $this->json(['error' => sprintf('The field %s must be a string or null', $field)], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['name']) && trim($data['name']) === '') {
            return $this->json(['error' => 'The field name must not be empty'], Response::HTTP_BAD_REQUEST);
        }
        if (isset($data['email']) && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'The email is not valid'], Response::HTTP_BAD_REQUEST);
        }
        $supplier->setName($data['name'] ?? $supplier->getName());
        $supplier->setEmail($data['email'] ?? null);
        $supplier->setCity($data['city'] ?? null);
        $supplier->setPhone($data['phone'] ?? null);
        $supplier->setAddress($data['address'] ?? null);

        $em->flush();

        // $this->logEntryService->createLogEntry('Supplier updated: ' . $supplier->getName());

        return $this->json(['message' => 'Supplier updated successfully'], Response::HTTP_OK);
    }

    // ─── DELETE: remove a supplier by ID ────────────────────────────────────────
    #[Route('/delete/{id}', name: 'app_supplier_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Delete(
        path: "/api/v1/supplier/delete/{id}",
        summary: "Delete a supplier",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the supplier to delete",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Supplier deleted successfully"),
            new OA\Response(
                response: 404,
                description: "Supplier not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Supplier not found")]
                )
            )
        ]
    )]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $company = $this->companyService->getCurrentCompany();
        $supplier = $company ? $em->getRepository(Supplier::class)->findOneBy(['id' => $id, 'company' => $company]) : null;
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        $em->remove($supplier);
        $em->flush();

        // $this->logEntryService->createLogEntry('Supplier deleted: ' . $supplier->getName());

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}