<?php

namespace App\Controller\Stock;

use App\Entity\Stock\Supplier;
use App\Repository\Stock\SupplierRepository;
use App\Service\LogEntryService;
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
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }
    
    #[Route('/list', name: 'app_supplier_display', methods: ['GET'])]
    #[OA\Get(
        path: "/api/v1/supplier/list",
        summary: "List all suppliers",
        description: "Returns a list of all suppliers",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of suppliers",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Supplier A"),
                            new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                            new OA\Property(property: "city", type: "string", example: "Paris"),
                            new OA\Property(property: "phone", type: "string", example: "+33123456789"),
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
    public function display(SupplierRepository $supplierRepository): JsonResponse
    {
        return $this->json($supplierRepository->findAll(), Response::HTTP_OK);
    }


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
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Supplier A"),
                            new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                            new OA\Property(property: "city", type: "string", example: "Paris"),
                            new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                            new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z") 
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
        $suppliers = $supplierRepository->findsupplier($sname);
        if (!$suppliers) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        // Return the found suppliers
                $data = [];
        foreach ($suppliers as $supplier) {
            $data[] = [
                'id' => $supplier->getId(),
                'name' => $supplier->getName(),
                'email' => $supplier->getEmail(),
                'city' => $supplier->getCity(),
                'phone' => $supplier->getPhone(),
            ];
        }       
        return $this->json($data, Response::HTTP_OK);
}

    #[Route('/create', name: 'app_supplier_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/supplier/create",
        summary: "Create a new supplier",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Supplier A"),
                    new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                    new OA\Property(property: "city", type: "string", example: "Paris"),
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
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Supplier A"),
                        new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                        new OA\Property(property: "city", type: "string", example: "Paris"),
                        new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                        new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z") 
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
     $required=['name','email','city','phone'];
        foreach($required as $field){
            if(empty($data[$field]) || !isset($data[$field])){
                return $this->json(['error'=>"The field $field is required"],Response::HTTP_BAD_REQUEST);
            }
        }
        $supplier = new Supplier();
        $supplier->setName($data['name']);
        $supplier->setEmail($data['email']);
        $supplier->setCity($data['city']);
        $supplier->setPhone($data['phone']);
        
        $em->persist($supplier);
        $em->flush();
        // Log the creation of the supplier
        $this->logEntryService->createLogEntry('Supplier created: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier created successfully'], Response::HTTP_CREATED);
    }

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
                    new OA\Property(property: "name", type: "string", example: "Updated Supplier"),
                    new OA\Property(property: "email", type: "string", example: "updated@email.com"),
                    new OA\Property(property: "city", type: "string", example: "Lyon"),
                    new OA\Property(property: "phone", type: "string", example: "+33498765432")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Supplier updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Updated Supplier"),
                        new OA\Property(property: "email", type: "string", example: "updated@email.com"),
                        new OA\Property(property: "city", type: "string", example: "Lyon"),
                        new OA\Property(property: "phone", type: "string", example: "+33498765432"),
                        new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")                    ]

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
    public function update(Supplier $supplier, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $required=['name','email','city','phone'];
        foreach($required as $field){
            if(empty($data[$field]) || !isset($data[$field])){
                return $this->json(['e rror'=>"The field $field is required"],Response::HTTP_BAD_REQUEST);
            }
        }

        $supplier->setName($data['name'] ?? $supplier->getName());
        $supplier->setEmail($data['email'] ?? $supplier->getEmail());
        $supplier->setCity($data['city'] ?? $supplier->getCity());
        $supplier->setPhone($data['phone'] ?? $supplier->getPhone());

        $em->flush();

        // Log the update of the supplier
        $this->logEntryService->createLogEntry('Supplier updated: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier updated successfully'], Response::HTTP_OK);
    
    }

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
            new OA\Response(
                response: 204,
                description: "Supplier deleted successfully"
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
    public function delete(Supplier $supplier, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($supplier);
        $em->flush();

        // Log the deletion of the supplier
        $this->logEntryService->createLogEntry('Supplier deleted: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier deleted successfully'], Response::HTTP_NO_CONTENT);
    }

}
