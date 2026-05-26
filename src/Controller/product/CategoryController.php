<?php

namespace App\Controller\Product;

use App\Entity\Product\Category;
use App\Repository\Product\CategoryRepository;
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
use RuntimeException;

#[OA\Tag(name: 'Category')]
final class CategoryController extends AbstractController
{
   private CompanyService $companyService;
    private CategoryRepository $categoryRepository;

    public function __construct(
        private Security $security,
        CompanyService $companyService,
        CategoryRepository $categoryRepository
    ) {
        $this->security = $security;
        $this->companyService = $companyService;
        $this->categoryRepository = $categoryRepository;
    }

    // ─── SEARCH: search categories by name ──────────────────────────────────────
    #[Route('/category/search/{cname}', name: 'app_category_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_WAITER')]
    #[OA\Get(
        path: "/api/v1/category/search/{cname}",
        summary: "Search category by name",
        description: "Searches for categories by their name",
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
                description: "Category found",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",           type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string",  example: "Electronics"),
                            new OA\Property(property: "description",  type: "string",  example: "Devices and gadgets"),
                            new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                            new OA\Property(property: "date_createAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "Category not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Category not found")]
                )
            )
        ]
    )]
    public function search(CategoryRepository $repo, string $cname): JsonResponse
    {
        $categories = $repo->findCategory($cname);
        if (!$categories) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id'           => $category->getId(),
                'categoryname' => $category->getCategoryname(),
                'description'  => $category->getDescription(),
                'company'      => $category->getCompany()?->getNameComp(),
                'date_createAt'=> $category->getCreatedAt(),
                'date_updateAt'=> $category->getUpdatedAt(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── LIST: retrieve all categories of the current company ───────────────────
    #[Route('/category/list', name: 'app_category_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_WAITER')]
    #[OA\Get(
        path: "/api/v1/category/list",
        summary: "List all categories",
        description: "Retrieves all categories belonging to the current company",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of categories",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",           type: "integer", example: 1),
                            new OA\Property(property: "categoryname", type: "string",  example: "Electronics"),
                            new OA\Property(property: "description",  type: "string",  example: "Devices and gadgets"),
                            new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                            new OA\Property(property: "date_createAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "date_updateAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No categories found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "Status", type: "string", example: "no categories registered")]
                )
            )
        ]
    )]
    public function display(): JsonResponse
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Filter categories by the current company only — not findAll()
        $categories = $this->categoryRepository->findBy(['company' => $company]);
        if (!$categories) {
            return $this->json(['Status' => 'No categories registered'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id'           => $category->getId(),
                'categoryname' => $category->getCategoryname(),
                'description'  => $category->getDescription(),
                'company'      => $category->getCompany()?->getNameComp(),
                'date_createAt'=> $category->getCreatedAt(),
                'date_updateAt'=> $category->getUpdatedAt(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── CREATE: create a new category and assign it to the current company ──────
    #[Route('/category/create', name: 'app_category_create', methods: ["POST"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/category/create",
        summary: "Create a new category",
        description: "Creates a new category and automatically assigns it to the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "categoryname", type: "string", example: "Electronics"),
                    new OA\Property(property: "description",  type: "string", example: "Devices and gadgets"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Category created successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "id",           type: "integer", example: 1),
                        new OA\Property(property: "categoryname", type: "string",  example: "Electronics"),
                        new OA\Property(property: "description",  type: "string",  example: "Devices and gadgets"),
                        new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                        new OA\Property(property: "date_createAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt",type: "string",  example: "2023-10-01T12:00:00Z"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Invalid input data")]
                )
            )
        ]
    )]
    public function create(Request $request, EntityManagerInterface $emi): JsonResponse
    {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        $required = ['categoryname', 'description'];
        foreach ($required as $field) {
            if (empty($data[$field]) || !isset($data[$field])) {
                return $this->json(['error' => "The field $field is required"], Response::HTTP_BAD_REQUEST);
            }
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        $categorie = new Category();
        $categorie->setCategoryname($data['categoryname']);
        $categorie->setDescription($data['description']);
        $categorie->setCompany($company); // ← automatically assigned from the current session

        $emi->persist($categorie);
        $emi->flush();

        // $this->logEntryService->createLogEntry(
        //     'Category created: ' . $categorie->getCategoryname() . ' by ' . $user->getUserIdentifier()
        // );

        return $this->json([
            'id'           => $categorie->getId(),
            'categoryname' => $categorie->getCategoryname(),
            'description'  => $categorie->getDescription(),
            'company'      => $company->getNameComp(),
            'date_createAt'=> $categorie->getCreatedAt(),
            'date_updateAt'=> $categorie->getUpdatedAt(),
        ], Response::HTTP_CREATED);
    }

    // ─── UPDATE: update an existing category ────────────────────────────────────
    #[Route('/category/modify/{id}', name: 'app_category_update', methods: ["PUT"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
        path: "/api/v1/category/modify/{id}",
        summary: "Update a category",
        description: "Allows updating a category by its ID",
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
                    new OA\Property(property: "description",  type: "string", example: "Updated description"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Category updated successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Category updated successfully")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Category not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Category not found")]
                )
            )
        ]
    )]
    public function update(Category $categorie, Request $request, EntityManagerInterface $emi): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $categorie->setCategoryname($data['categoryname'] ?? $categorie->getCategoryname());
        $categorie->setDescription($data['description'] ?? $categorie->getDescription());

        $emi->flush();

        // $this->logEntryService->createLogEntry('Category updated: ' . $categorie->getCategoryname());

        return $this->json(['message' => 'Category updated successfully'], Response::HTTP_OK);
    }

    // ─── DELETE: remove a category by ID ────────────────────────────────────────
    #[Route('/category/delete/{id}', name: 'app_category_delete', methods: ["DELETE"])]
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
            new OA\Response(response: 204, description: "Category deleted successfully"),
            new OA\Response(
                response: 404,
                description: "Category not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Category not found")]
                )
            )
        ]
    )]
    public function delete(Category $categorie, EntityManagerInterface $emi): JsonResponse
    {
        $emi->remove($categorie);
        $emi->flush();

        // $this->logEntryService->createLogEntry('Category deleted: ' . $categorie->getCategoryname());

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}