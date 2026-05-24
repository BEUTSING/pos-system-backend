<?php

namespace App\Controller\Company;

use App\Service\Company\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Company')]
final class CompanyController extends AbstractController
{
    public function __construct(private CompanyService $companyService)
    {
    }

    // Search company by name
    #[Route('/company/search/{cname}', name: 'app_company_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/v1/company/search/{cname}',
        summary: 'Search company by name',
        description: 'Searches for companies by their name',
        parameters: [
            new OA\Parameter(
                name: 'cname',
                in: 'path',
                required: true,
                description: 'Company name to search for',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Company found',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id',        type: 'integer', example: 1),
                            new OA\Property(property: 'nameComp',  type: 'string',  example: 'Acme Corp'),
                            new OA\Property(property: 'emailComp', type: 'string',  example: 'contact@acme.com'),
                            new OA\Property(property: 'phone',     type: 'string',  example: '+237600000000'),
                            new OA\Property(property: 'city',      type: 'string',  example: 'Douala'),
                            new OA\Property(property: 'numEmpl',   type: 'integer', example: 50),
                            new OA\Property(property: 'siteWeb',   type: 'string',  example: 'https://acme.com'),
                            new OA\Property(property: 'owner',     type: 'string',  example: 'admin@acme.com'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Company not found')
                    ]
                )
            )
        ]
    )]
    public function search(string $cname): JsonResponse
    {
        try {
            $data = $this->companyService->searchC($cname);
            return $this->json($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // List all companies of the connected user
    #[Route('/company/list', name: 'app_company_list', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/v1/company/list',
        summary: 'List all companies',
        description: 'Retrieves the list of companies owned by the authenticated user',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of companies',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id',        type: 'integer', example: 1),
                            new OA\Property(property: 'nameComp',  type: 'string',  example: 'Acme Corp'),
                            new OA\Property(property: 'emailComp', type: 'string',  example: 'contact@acme.com'),
                            new OA\Property(property: 'phone',     type: 'string',  example: '+237600000000'),
                            new OA\Property(property: 'city',      type: 'string',  example: 'Douala'),
                            new OA\Property(property: 'numEmpl',   type: 'integer', example: 50),
                            new OA\Property(property: 'siteWeb',   type: 'string',  example: 'https://acme.com'),
                            new OA\Property(property: 'owner',     type: 'string',  example: 'admin@acme.com'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No companies found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'No companies registered')
                    ]
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        try {
            $data = $this->companyService->listC();
            return $this->json($data, Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return $this->json(['status' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // Create a new company
    #[Route('/company/create', name: 'app_company_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/v1/company/create',
        summary: 'Create a new company',
        description: 'Allows creating a new company linked to the authenticated user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'nameComp',  type: 'string',  example: 'Acme Corp'),
                    new OA\Property(property: 'emailComp', type: 'string',  example: 'contact@acme.com'),
                    new OA\Property(property: 'phone',     type: 'string',  example: '+237600000000'),
                    new OA\Property(property: 'city',      type: 'string',  example: 'Douala'),
                    new OA\Property(property: 'numEmpl',   type: 'integer', example: 50),
                    new OA\Property(property: 'siteWeb',   type: 'string',  example: 'https://acme.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Company created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id',        type: 'integer', example: 1),
                        new OA\Property(property: 'nameComp',  type: 'string',  example: 'Acme Corp'),
                        new OA\Property(property: 'emailComp', type: 'string',  example: 'contact@acme.com'),
                        new OA\Property(property: 'phone',     type: 'string',  example: '+237600000000'),
                        new OA\Property(property: 'city',      type: 'string',  example: 'Douala'),
                        new OA\Property(property: 'numEmpl',   type: 'integer', example: 50),
                        new OA\Property(property: 'siteWeb',   type: 'string',  example: 'https://acme.com'),
                        new OA\Property(property: 'owner',     type: 'string',  example: 'admin@acme.com'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'The field nameComp is required')
                    ]
                )
            )
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->companyService->createC($request);
            return $this->json($data, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // Update a company
    #[Route('/company/modify/{id}', name: 'app_company_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/v1/company/modify/{id}',
        summary: 'Update a company',
        description: 'Allows updating a company by its ID (owner only)',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the company to update',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'nameComp',  type: 'string',  example: 'Updated Name'),
                    new OA\Property(property: 'emailComp', type: 'string',  example: 'new@acme.com'),
                    new OA\Property(property: 'phone',     type: 'string',  example: '+237611111111'),
                    new OA\Property(property: 'city',      type: 'string',  example: 'Yaoundé'),
                    new OA\Property(property: 'numEmpl',   type: 'integer', example: 100),
                    new OA\Property(property: 'siteWeb',   type: 'string',  example: 'https://new.acme.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Company updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Company updated successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Company not found')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Access denied: you are not the owner of this company')
                    ]
                )
            )
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->companyService->updateC($id, $request);
            return $this->json($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    // Delete a company
    #[Route('/company/delete/{id}', name: 'app_company_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/v1/company/delete/{id}',
        summary: 'Delete a company',
        description: 'Allows deleting a company by its ID (owner only)',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the company to delete',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Company deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Company not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Company not found')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Access denied: you are not the owner of this company')
                    ]
                )
            )
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->companyService->deleteC($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}