<?php

namespace App\Controller\product;

use App\Entity\Product\Product;
use App\Repository\Product\CategoryRepository;
use App\Repository\Product\ProductRepository;
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

#[OA\Tag(name: 'Product')]
final class ProductController extends AbstractController
{
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }
    #[Route('/product/search/{pname}', name: 'app_product_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
   #[OA\Get(
        path: "/api/v1/product/search/{pname}",
        summary: "Search for a product by name",
        description: "Returns a list of products matching the search criteria",
        parameters: [
            new OA\Parameter(
                name: "pname",
                in: "path",
                required: true,
                description: "The name of the product to search for",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Products found successfully",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "productname", type: "string"),
                            new OA\Property(property: "category", type: "string", nullable: true),
                            new OA\Property(property: "supplier", type: "string", nullable: true),
                            new OA\Property(property: "saleprice", type: "number", format: "float"),
                            new OA\Property(property: "purchaseprice", type: "number", format: "float"),
                            new OA\Property(property: "quantity", type: "integer"),
                            new OA\Property(property: "minimumstock", type: "integer")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Product not found")]
                )
            )
        ]
    )]

    public function search(ProductRepository $repo, string $pname): JsonResponse
    {
        $products = $repo->findProduct($pname);
        if (!$products) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'productname' => $product->getProductname(),
                'category' => $product->getCategory() ? $product->getCategory()->getCategoryname() : null,
                'supplier' => $product->getSupplier() ? $product->getSupplier()->getName() : null,
                'saleprice' => $product->getSaleprice(),
                'purchaseprice' => $product->getPurchaseprice(),
                'quantity' => $product->getQuantity(),
                'minimumstock' => $product->getMinimumstock(),
            ];
        }
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/product/list', name: 'app_product_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
            path: "/api/v1/product/list",
            summary: "List all products",
            description: "Returns a list of all products in the database",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "List of products retrieved successfully",
                    content: new OA\JsonContent(
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "productname", type: "string"),
                                new OA\Property(property: "category", type: "string", nullable: true),
                                new OA\Property(property: "supplier", type: "string", nullable: true),
                                new OA\Property(property: "saleprice", type: "number", format: "float"),
                                new OA\Property(property: "purchaseprice", type: "number", format: "float"),
                                new OA\Property(property: "quantity", type: "integer"),
                                new OA\Property(property: "minimumstock", type: "integer"),
                                new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                                new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                            ]
                        )
                    )
                ),
                new OA\Response(
                    response: 404,
                    description: "No products found",
                    content: new OA\JsonContent(
                        properties: [new OA\Property(property: "Status", type: "string", example: "no products registered")]
                    )
                )
            ]
        )]
    public function list(ProductRepository $repos): JsonResponse
    {
       

    }

    #[Route('/product/create', name: 'app_product_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/product/create",
        summary: "Create a new product",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "productname", type: "string", example: "Example Product"),
                    new OA\Property(property: "category", type: "integer", description: "Category ID", example: 1),
                    new OA\Property(property: "supplier", type: "integer", nullable: true, description: "Supplier ID (optional)", example: 1),
                    new OA\Property(property: "saleprice", type: "number", format: "float", example: 19.99),
                    new OA\Property(property: "purchaseprice", type: "number", format: "float", example: 10.50),
                    new OA\Property(property: "quantity", type: "integer", example: 100),
                    new OA\Property(property: "minimumstock", type: "integer", example: 10)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Product created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "productname", type: "string",example:"product name"),
                        new OA\Property(property: "category", type: "string", nullable: true, example:"Electronics"),
                        new OA\Property(property: "supplier", type: "string", nullable: true, example: "Supplier Name"),
                        new OA\Property(property: "saleprice", type: "number", format: "float", example:1000),
                        new OA\Property(property: "purchaseprice", type: "number", format: "float", example:2000),
                        new OA\Property(property: "quantity", type: "integer", example: 50),
                        new OA\Property(property: "minimumstock", type: "integer", example: 5),
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
    public function create(Request $request): JsonResponse
    {
        try{}
    }

    #[Route('/product/modify/{id}', name: 'app_product_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
        path: "/api/v1/product/modify/{id}",
        summary: "Modify an existing product",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the product to modify",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "productname", type: "string", nullable: true, example: "Updated Product Name"),
                    new OA\Property(property: "category", type: "integer", nullable: true, description: "Category ID"),
                    new OA\Property(property: "supplier", type: "integer", nullable: true, description: "Supplier ID"),
                    new OA\Property(property: "saleprice", type: "number", format: "float", nullable: true, example: 25.50),
                    new OA\Property(property: "purchaseprice", type: "number", format: "float", nullable: true, example: 15.00),
                    new OA\Property(property: "quantity", type: "integer", nullable: true, example: 150),
                    new OA\Property(property: "minimumstock", type: "integer", nullable: true, example: 20)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Product updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "productname", type: "string", nullable: true, example: "Updated Product Name"),
                        new OA\Property(property: "category", type: "integer", nullable: true, description: "Category ID"),
                        new OA\Property(property: "supplier", type: "integer", nullable: true, description: "Supplier ID"),
                        new OA\Property(property: "saleprice", type: "number", format: "float", nullable: true, example: 25.50),
                        new OA\Property(property: "purchaseprice", type: "number", format: "float", nullable: true, example: 15.00),
                        new OA\Property(property: "quantity", type: "integer", nullable: true, example: 150),
                        new OA\Property(property: "minimumstock", type: "integer", nullable: true, example: 20),
                        new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")                        ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product or Category/Supplier not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Product not found")]
                )
            )
        ]
    )]

    public function update(Product $product, Request $request, EntityManagerInterface $em,CategoryRepository $categoryrepository,SupplierRepository $supplierrepository): JsonResponse
    {
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Delete(
        path: "/api/v1/product/delete/{id}",
        summary: "Delete a product",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the product to delete",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product deleted successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Product deleted successfully")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Product not found")]
                )
            )
        ]
    )]

    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
    }

}
