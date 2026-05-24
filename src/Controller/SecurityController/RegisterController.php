<?php

namespace App\Controller\SecurityController;

use App\Service\Security\RegisterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Authentication')]
final class RegisterController extends AbstractController
{
    
    private $registerService;
    public function __construct( RegisterService $registerService){
        $this->registerService = $registerService;
        
    }

    #[Route('/user/register', name: 'app_register_create', methods: ['POST'])]
    #[OA\Post(
        path:'/api/v1/user/register',
        summary: "User registration",
        description: "Allows registering a new user with name, phone, city, color, email, and password.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Beutsing jeanne"),
                    new OA\Property(property: "phone", type: "string", example: "+237 682341110"),
                    new OA\Property(property: "city", type: "string", example: "New York"),
                    new OA\Property(property: "color", type: "string", example: "blue"),
                    new OA\Property(property: "email", type: "string", example: "beutsing@gmail.com"),
                    new OA\Property(property: "password", type: "string", example: "bk123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User created successfully",
                content: new OA\JsonContent(
                    properties: [    
                        new OA\Property(property: "status", type: "string", example: "The user has been created successfully"),
                        new OA\Property(property:"name", type: "string", example: "Beutsing jeanne"),
                    new OA\Property(property: "phone", type: "string", example: "+237 682341110"),
                    new OA\Property(property: "city", type: "string", example: "New York"),
                    new OA\Property(property: "color", type: "string", example: "blue"),
                    new OA\Property(property: "email", type: "string", example: "beutsing@gmail.com"),
                    new OA\Property(property: "password", type: "string", example: "bk123")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad request",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid input data")
                    ]
                )
            ),
             new OA\Response(response: 409, description: "this user already exists",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "this user already exists")
                    ]
                )
            )
        ]      
    )]
    
    public function register(Request $request): JsonResponse
    {
        try {

        $user = $this->registerService->register($request);

        return new JsonResponse([$user,
            'status' => 'User created successfully',
        ], Response::HTTP_CREATED);

    } catch (\InvalidArgumentException $e) {
        return new JsonResponse(
            ['error' => $e->getMessage()],
            Response::HTTP_BAD_REQUEST
        );

    } catch (\RuntimeException $e) {
        return new JsonResponse(
            ['error' => $e->getMessage()],
            Response::HTTP_CONFLICT
        );
    }
    }
      
}