<?php

namespace App\Controller\SecurityController;

use App\Entity\Security\User;
use App\Enum\RoleUser;
use App\Repository\Security\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Authentication')]
final class RegisterController extends AbstractController
{
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
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                    new OA\Property(property: "city", type: "string", example: "New York"),
                    new OA\Property(property: "color", type: "string", example: "blue"),
                    new OA\Property(property: "email", type: "string", example: "exemple@gmail.com"),
                    new OA\Property(property: "password", type: "string", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User created successfully",
                content: new OA\JsonContent(
                    properties: [    
                        new OA\Property(property: "status", type: "string", example: "The user has been created successfully")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad request",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid input data")
                    ]
                )
            )
        ]      
    )]
    public function register(Request $request,EntityManagerInterface $em,UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data=json_decode($request->getContent(), true);
        
        $user=new User();
        $user->setName($data['name']);
        $user->setPhone($data['phone']);
        $user->setCity($data['city']);
        $user->setColor($data['color']);
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $em->persist($user);
        $em->flush();
        return new JsonResponse(['status=> The user has been created successfully'],Response::HTTP_CREATED);
    }

    #[Route('/user/modify', name:'app_regiter', methods: ['PUT'])]
   #[OA\Put(
        path: '/api/v1/user/modify',
        summary: "Update user information",
        description: "Allows updating user information such as name, phone, city, color, email, password, and roles.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Jane Doe", nullable: true),
                    new OA\Property(property: "phone", type: "string", example: "+0987654321", nullable: true),
                    new OA\Property(property: "city", type: "string", example: "Los Angeles", nullable: true),
                    new OA\Property(property: "color", type: "string", example: "red", nullable: true),
                    new OA\Property(property: "email", type: "string", example: "jane.doe@example.com", nullable: true),
                    new OA\Property(property: "password", type: "string", example: "newSecurePassword123", nullable: true),
                    new OA\Property(
                        property: "role",
                        type: "array",
                        nullable: true,
                        items: new OA\Items(type: "string", example: "ROLE_USER"),
                        description: "Array of roles to assign to the user"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User updated successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "status", type: "string", example: "User updated successfully")]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - invalid data",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Invalid role: ROLE_INVALID. Allowed roles: ROLE_USER, ROLE_ADMIN")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "User not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "User not found")]
                )
            )
        ]
    )]

    public function updateregister(Request $request, EntityManagerInterface $entityManager,UserRepository $userrepo,UserPasswordHasherInterface $passwordHasher): JsonResponse{
        $data=json_decode($request->getContent(), true);

        $user =$userrepo->find($data['user_id']);
        if (!$user) {
            throw new \Exception("User not found" );
        }

        if (isset($data["name"])) $user->setName($data["name"]);
        if (isset($data["phone"])) $user->setPhone($data["phone"]);
        if (isset($data["city"])) $user->setCity($data["city"]);
        if (isset($data["color"])) $user->setColor($data["color"]);
        if (isset($data["email"])) $user->setEmail($data["email"]);
     
       if(isset($data["role"])){
             $allowedRoles = array_column(RoleUser::cases(), 'value');

            foreach ($data["role"] as $r) {
                if (!in_array($r, $allowedRoles)) {
                    return new JsonResponse([
                        'error' => 'Invalid role: ' . $r . '. Allowed roles: ' . implode(', ', $allowedRoles)
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

         $user->setRoles($data["role"]);
       }
        if (isset($data["password"]))
        $user->setPassword($passwordHasher->hashPassword($user, $data["password"]));

        $entityManager->flush();
        return new JsonResponse(['status' => 'User updated successfully'], Response::HTTP_OK);

    }
}
