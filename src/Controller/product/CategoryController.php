<?php

namespace App\Controller\product;

use App\Entity\Product\Category;
use App\Repository\Product\CategoryRepository;
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

#[OA\Tag(name: 'Category')]
final class CategoryController extends AbstractController
{
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }
// search category
      #[Route('/category/search/{cname}', name: 'app_category_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path:"/api/v1/category/search/{cname}",
        summary:"Search category by name",
        description:"Searches for categories by their name",
        parameters: [
            new OA\Parameter(
            name: "cname", 
            in: "path", 
            required: true, 
            description: "Category name to search for",
            schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description:"category found",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                            new OA\Property(property: "description", type: "string", example: "Devices and gadgets"),
                            new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                        ]

                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Category not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Category not found")
                    ]
                )
            )
        ]

    )]
    public function search(CategoryRepository $repo, string $cname): JsonResponse
 {

        // Find categories by name
     $categories = $repo->findCategory($cname);
        if (!$categories) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }
        // Return the found categories
        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'categoryname' => $category->getCategoryname(),
                'description' => $category->getDescription(),
            ];
        }
        // Return the data as JSON response
     return $this->json($data, Response::HTTP_OK);
     }

    #[Route('/category/list', name: 'app_category_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/category/list",
        summary: "List all categories",
        description:" Retrieves a list of all categories",
        responses: [
            new OA\Response(
                response: 200,
                description:"List of categories",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                            new OA\Property(property: "description", type: "string", example: "Devices and gadgets"),
                            new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No categories found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "Status", type: "string", example:"no categories registered")
                    ]
                )
            )
        ]    
    )]

public function display(CategoryRepository $repo): JsonResponse
{
    $categorie=$repo->findAll();
    $data = [];
    foreach ($categorie as $category) {
        $data[] = [
            'id' => $category->getId(),
            'categoryname' => $category->getCategoryname(),
            'description' => $category->getDescription(),
        ];
    }
    // Return the data as JSON response 

    return $this->json($data, Response:: HTTP_OK);
}

    #[Route('/category/create', name: 'app_category_create',methods:["POST"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path:"/api/v1/category/create",
        summary:"Create a new category",
        description:"Allows creating a new category with name and description",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                    new OA\Property(property: "description", type: "string", example: "Devices and gadgets")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description:"category created successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                        new OA\Property(property: "description", type: "string", example: "Devices and gadgets"),
                        new OA\Property(property: "status", type: "string", example: "The category has been created successfully"),
                        new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request",
                content: new OA\JsonContent(        
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid input data")
                    ]
                )
            )
        ]
    )]
    public function create(Request $request,EntityManagerInterface $emi, Security $security): JsonResponse
    {
        $user = $security->getUser();

        $data=json_decode($request->getContent(),true);
         
        $categorie=new Category();
        $categorie->setCategoryname($data['categoryname']);
        $categorie->setDescription($data['description']);
       
        $emi->persist($categorie);
        $emi->flush();
        // Log the creation of the category
        $this->logEntryService->createLogEntry('Category created: ' . $categorie->getCategoryname());
    return $this->json($categorie, Response::HTTP_CREATED);
    }

    #[Route('/category/modify/{id}', name: 'app_category_update',methods:["PUT"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
        path: "/api/v1/category/modify/{id}",
        summary: "Update a category",
        description: "Allows updating a category by its Id",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the category to update",
                schema: new OA\Schema(type: 'integer')
            )
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "categoryname", type: "string", example: "Updated Category Name"),
                        new OA\Property(property: "description", type: "string", example: "Updated description"),
                    ]
                )
            ),
            responses: [
                new OA\Response(   
                    response: 200,
                    description: "Category updated successfully",
                    content: new OA\JsonContent(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                            new OA\Property(property: "description", type: "string", example: "Devices and gadgets"),
                            new OA\Property(property: "status", type: "string", example: "Category updated successfully"),
                            new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                        ]
                    )
                ),
                new OA\Response(
                    response: 404,
                    description: "Category not found",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Category not found")
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Bad request - invalid data",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Invalid input data")
                        ]
                    )
                )
            ]
    )]

    public function update(Category $categorie, Request $request,EntityManagerInterface $emi): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
        
        $categorie->setCategoryname($data['categoryname']??$categorie->getCategoryname() );
        $categorie->setDescription($data['description']??$categorie->getDescription() );
        

        $emi->flush();
        // Log the update of the category
        $this->logEntryService->createLogEntry('Category updated: ' . $categorie->getCategoryname());
    return $this->json($categorie, Response::HTTP_OK);

    }
    
    #[Route('/category/delete/{id}', name: 'app_category_delete',methods:["DELETE"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Delete(
        path: "/api/v1/category/delete/{id}",
        summary: "Delete a category",
        description: "Allows deleting a category by its ID",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID of the category to delete",
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Category deleted successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Category not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Category not found")
                    ]
                )
            )
        ]
    )]

    public function delete(Category $categorie,EntityManagerInterface $emi): JsonResponse
    {        
        $emi->remove($categorie);
        $emi->flush();
        // Log the deletion of the category
        $this->logEntryService->createLogEntry('Category deleted: ' . $categorie->getCategoryname());
           return $this->json(Null, 204);

    }
}
