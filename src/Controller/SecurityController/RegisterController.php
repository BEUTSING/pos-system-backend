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

use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register/create', name: 'app_register_create', methods: ['POST'])]
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

    #[Route('/register/modifier', name:'app_regiter', methods: ['PUT'])]

    public function updateregister(Request $request, EntityManagerInterface $entityManager,UserRepository $userrepo,UserPasswordHasherInterface $passwordHasher): JsonResponse{
        $data=json_decode($request->getContent(), true);

        $user = $entityManager->$userrepo->find($data['id']);
        if (!$user) {
            throw new \Exception("User not found" );
        }

        if (isset($data["name"])) $user->setName($data["name"]);
        if (isset($data["phone"])) $user->setPhone($data["phone"]);
        if (isset($data["city"])) $user->setCity($data["city"]);
        if (isset($data["color"])) $user->setColor($data["color"]);
        if (isset($data["email"])) $user->setEmail($data["email"]);

        
       if(isset($data["role"])){
             if (!in_array($data['role'], array_column(RoleUser::cases(), 'value'))) {
            return new JsonResponse([
                'error' => 'Invalid role. Allowed roles: ' . implode(', ', array_column(RoleUser::cases(), 'value'))
            ], Response::HTTP_BAD_REQUEST);}
      
         $user->setRole($data["role"]);
       }
        $user->setRole($data["role"]);
        if (isset($data["password"]))
        $user->setPassword($passwordHasher->hashPassword($user, $data["password"]));

        $entityManager->flush();
        return new JsonResponse(['status' => 'User updated successfully'], Response::HTTP_OK);

    }
}
