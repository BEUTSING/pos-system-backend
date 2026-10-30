<?php

namespace App\Service\Security;

use App\Entity\Company\Company;
use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use App\Service\Company\CompanyService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use RuntimeException;

class RegisterService
{
    private $em;
    private $passwordHasher;
    private $userrepo;
    private $companyService;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userrepo,
        CompanyService $companyService
    ) {
        $this->em             = $em;
        $this->passwordHasher = $passwordHasher;
        $this->userrepo       = $userrepo;
        $this->companyService = $companyService;
    }

    public function register(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        $required = ['name', 'phone', 'city', 'color', 'email', 'password', 'companyName'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException('The field ' . $field . ' is required');
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The email is not valid');
        }

        $userexists = $this->userrepo->findOneBy(['email' => $data['email']]);
        if ($userexists) {
            throw new RuntimeException('this user already exists');
        }

        $company = null;

        $user = new User();
        $user->setName($data['name']);
        $user->setPhone($data['phone']);
        $user->setCity($data['city']);
        $user->setColor($data['color']);
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_ADMIN']);
        
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $company = new Company();
        $company->setNameComp($data['companyName']);
        $company->setEmailComp($data['companyEmail'] ?? $data['email']);
        $company->setPhone($data['phone']);
        $company->setCity($data['city']);
        $company->setNumEmpl(1);
        $company->setSiteWeb($data['siteWeb'] ?? '');
        $company->setAddress($data['address'] ?? null);
        $company->setCountry($data['country'] ?? null);
        // Currency is optional: fall back to MAD when it is missing or malformed.
        $currency = strtoupper(trim((string) ($data['currency'] ?? 'MAD')));
        $company->setCurrency(strlen($currency) === 3 ? $currency : 'MAD');
        $company->setLogo($data['logo'] ?? null);
        $company->setOwner($user);

        $this->em->persist($user);
        $this->em->persist($company);
        $this->em->flush();

        return [
            'id'      => $user->getId(),
            'name'    => $user->getName(),
            'phone'   => $user->getPhone(),
            'city'    => $user->getCity(),
            'color'   => $user->getColor(),
            'email'   => $user->getEmail(),
            'roles'   => $user->getRoles(),
            'company' => $company->getNameComp(),
            'companyId' => $company->getId(),
            'companyData' => [
                'id' => $company->getId(),
                'name' => $company->getNameComp(),
                'email' => $company->getEmailComp(),
                'phone' => $company->getPhone(),
                'city' => $company->getCity(),
                'country' => $company->getCountry(),
                'address' => $company->getAddress(),
                'logo' => $company->getLogo(),
                'currency' => $company->getCurrency(),
                'modules' => ['clients' => true, 'suppliers' => true],
            ],
        ];
    }
}