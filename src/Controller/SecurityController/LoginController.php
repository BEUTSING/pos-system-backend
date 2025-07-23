<?php

namespace App\Controller\SecurityController;

use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function index(Request $request,UserPasswordHasher $passwordHarsher,EntityManagerInterface $entityManager,JWTTokenManagerInterface $JWTManager): 
    JsonResponse{
        $date=json_decode($request->getContent(),true);

        $email = $date['email'] ?? null;
        $password = $date['password'] ?? null;
         if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
         }
         $user= $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

         if(!$user || !$passwordHarsher->isPasswordValid($user, $password)){
            return new JsonResponse(['error' => 'Please check your email and password.'], Response::HTTP_UNAUTHORIZED);
         }
         $token= $JWTManager->create($user);
            return new JsonResponse(['token' => $token], Response::HTTP_OK);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse(['message' => 'Logged out successfully'], Response::HTTP_OK);
    }

}
