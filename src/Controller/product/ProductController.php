<?php

namespace App\Controller\product;

use App\Entity\Product\Product;

use App\Service\Product\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Product')]
final class ProductController extends AbstractController
{
        private $productService;
    public function __construct(ProductService $productService )
    {
        $this->productService = $productService;
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

    public function search(string $pname): JsonResponse
    {
        try {
            $data = $this->productService->searchP($pname);
            return $this->json($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
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

    
        public function list(): JsonResponse
        {
            try{
                    $data= $this->productService->listP();
                    return $this->json($data, Response::HTTP_OK);
                }catch(\RuntimeException $e){
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);

            }
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
                        new OA\Property(property: "category",
                         type: "object",
                         nullable: false, 
                        properties:[
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                            new OA\Property(property: "description", type: "string", example: "Devices and gadgets"),
                        ]),
                        new OA\Property(property: "supplier", 
                        type: "object", nullable:"true",
                        properties:[
                             new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Supplier A"),
                        new OA\Property(property: "email", type: "string", example: "supplierA@email.com"),
                        new OA\Property(property: "city", type: "string", example: "Paris"),
                        new OA\Property(property: "phone", type: "string", example: "+33123456789"),
                        ]), 
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
        try{

            $data= $this->productService->createP($request);
            return $this->json($data, Response::HTTP_CREATED);
        }catch(\InvalidArgumentException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);  
       }
       catch(\RuntimeException $e){
        return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);}
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

    
    public function update(int $id, Request $request ): JsonResponse
    {
        try{
            $data= $this->productService->updateP($id,$request);
            return $this->json($data, Response::HTTP_OK);
        } 
        catch(\InvalidArgumentException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);

        } 
        catch(\RuntimeException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

    }

    #[Route('/product/delete/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Delete(
        path: "/api/v1/product/delete/{id}",
        summary: " Delete a product",
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

    public function delete(int $id): JsonResponse
    {
        try{
           $data= $this->productService->deleteP($id);
            return  $this->json(['message' => $data], Response::HTTP_OK);
        }catch(\InvalidArgumentException $e){
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);  
        }
    }
}


