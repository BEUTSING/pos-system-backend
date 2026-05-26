<?php

namespace App\Service\Security;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterService
{
    private $em;
    private $passwordHasher;
    private $userrepo;
    public function __construct(EntityManagerInterface $em,
                                 UserPasswordHasherInterface $passwordHasher,UserRepository $userrepo)
    {
        $this->em=$em;
        $this->passwordHasher=$passwordHasher;
        $this->userrepo=$userrepo;
    }

    // create a Compte
    public function register(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
         $required =['name','phone','city','color','email'];
         foreach($required as $field){
            if(empty($data[$field])){
                throw new \InvalidArgumentException('The field '.$field.' is required');
            }
         }
            if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){
                throw new \InvalidArgumentException('The email is not valid');
            }
            $userexists=$this->userrepo->findOneBy(['email'=>$data['email']]);
            if($userexists){
                throw new \RuntimeException('this user already exists');
            }

        $user=new User();
        $user->setName($data['name']);
        $user->setPhone($data['phone']);
        $user->setCity($data['city']);
        $user->setColor($data['color']);
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

       $this->em->persist($user);
        $this->em->flush();
        $info[]=[
            "id"=>$user->getId(),
            "name"=>$user->getName(),
            "phone"=>$user->getPhone(),
            "city"=>$user->getCity(),
            "color"=>$user->getColor(),
            "email"=>$user->getEmail()
        ];
        return $info;
    }
}