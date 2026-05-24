<?php

namespace App\Controller\SecurityController;

use App\Service\Security\UsersSercice;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Users')]
final class UsersController extends AbstractController
{
    private $UsersSercice;

    public function __construct(UsersSercice $UsersSercice)
    {
        $this->UsersSercice = $UsersSercice;
    }

    // ─── CREATE: create a new user and assign them to the current company ────────
    #[Route('/user/create', name: 'app_user_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/v1/user/create',
        summary: 'User creation',
        description: 'Creates a new user and automatically assigns them to the current company',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name',     type: 'string',  example: 'Beutsing Jeanne'),
                    new OA\Property(property: 'phone',    type: 'string',  example: '+237 682341110'),
                    new OA\Property(property: 'city',     type: 'string',  example: 'Douala'),
                    new OA\Property(property: 'color',    type: 'string',  example: 'blue'),
                    new OA\Property(property: 'email',    type: 'string',  example: 'beutsing@gmail.com'),
                    new OA\Property(property: 'password', type: 'string',  example: 'bk123'),
                    new OA\Property(
                        property: 'role',
                        type: 'array',
                        items: new OA\Items(type: 'string', example: 'ROLE_WAITER'),
                        description: 'Allowed: ROLE_WAITER, ROLE_TELLER, ROLE_MANAGER, ROLE_ADMIN'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',      type: 'integer', example: 1),
                        new OA\Property(property: 'name',    type: 'string',  example: 'Beutsing Jeanne'),
                        new OA\Property(property: 'phone',   type: 'string',  example: '+237 682341110'),
                        new OA\Property(property: 'city',    type: 'string',  example: 'Douala'),
                        new OA\Property(property: 'color',   type: 'string',  example: 'blue'),
                        new OA\Property(property: 'email',   type: 'string',  example: 'beutsing@gmail.com'),
                        new OA\Property(property: 'roles',   type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'company', type: 'string',  example: 'Acme Corp'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid input data')]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'User already exists',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'This user already exists')]
                )
            )
        ]
    )]
    public function createUsers(Request $request): JsonResponse
    {
        try {
            $user = $this->UsersSercice->createUsers($request);
            return new JsonResponse($user, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    // ─── LIST: retrieve all users of the current company ─────────────────────────
    #[Route('/user/list', name: 'app_user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/v1/user/list',
        summary: 'List all users of the current company',
        description: 'Retrieves all users belonging to the authenticated admin company',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of users',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id',      type: 'integer', example: 1),
                            new OA\Property(property: 'name',    type: 'string',  example: 'Beutsing Jeanne'),
                            new OA\Property(property: 'phone',   type: 'string',  example: '+237 692170034'),
                            new OA\Property(property: 'city',    type: 'string',  example: 'Douala'),
                            new OA\Property(property: 'color',   type: 'string',  example: 'red'),
                            new OA\Property(property: 'email',   type: 'string',  example: 'jeanne@gmail.com'),
                            new OA\Property(property: 'roles',   type: 'array', items: new OA\Items(type: 'string')),
                            new OA\Property(property: 'company', type: 'string',  example: 'Acme Corp'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No users found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'No users found for this company')]
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        try {
            $data = $this->UsersSercice->listUsers();
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── UPDATE: update an existing user's information ──────────────────────────
    #[Route('/user/modify/{id}', name: 'app_user_modify', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        path: '/api/v1/user/modify/{id}',  // ← fixed: {id} added
        summary: 'Update user information',
        description: 'Allows updating user information such as name, phone, city, color, email, password, and roles',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the user to update',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name',     type: 'string',  example: 'Beutsing Jeanne',  nullable: true),
                    new OA\Property(property: 'phone',    type: 'string',  example: '+237 692170034',   nullable: true),
                    new OA\Property(property: 'city',     type: 'string',  example: 'Douala',           nullable: true),
                    new OA\Property(property: 'color',    type: 'string',  example: 'red',              nullable: true),
                    new OA\Property(property: 'email',    type: 'string',  example: 'jeanne@gmail.com', nullable: true),
                    new OA\Property(property: 'password', type: 'string',  example: 'admin123',         nullable: true),
                    new OA\Property(
                        property: 'role',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(type: 'string', example: 'ROLE_WAITER'),
                        description: 'Allowed: ROLE_WAITER, ROLE_TELLER, ROLE_MANAGER, ROLE_ADMIN'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'status', type: 'string', example: 'User updated successfully')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid data',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'Invalid role')]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found')]
                )
            )
        ]
    )]
    public function updateUsers(Request $request, int $id): JsonResponse
    {
        try {
            $data = $this->UsersSercice->updateUsers($request, $id);
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
        // ← fixed: removed the duplicate service call that was here
    }

    // ─── DELETE: remove a user by ID ────────────────────────────────────────────
    #[Route('/user/delete/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/v1/user/delete/{id}',
        summary: 'Delete a user by ID',
        description: 'Permanently removes a user from the system',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID of the user to delete',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully')]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'User not found')]
                )
            )
        ]
    )]
    public function deleteUser(int $id): JsonResponse
    {
        try {
            $data = $this->UsersSercice->deleteUsers($id); // ← fixed: deleteUsers (capital U)
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}