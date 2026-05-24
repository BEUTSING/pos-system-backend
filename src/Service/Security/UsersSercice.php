<?php

namespace App\Service\Security;

use App\Entity\Security\User;
use App\Enum\RoleUser;
use App\Repository\Security\UserRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersSercice
{
    private $em;
    private $passwordHasher;
    private $userrepo;
    private $logEntryService;

    public function __construct(
        EntityManagerInterface $em,
        LogEntryService $logEntryService,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userrepo
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->userrepo = $userrepo;
        $this->logEntryService = $logEntryService;
    }

    // Create user
    public function createUsers(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $required = ['name', 'phone', 'city', 'color', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException('The field ' . $field . ' is required');
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The email is not valid');
        }

        // Check if user already exists
        $userExists = $this->userrepo->findOneBy(['email' => $data['email']]);
        if ($userExists) {
            throw new \RuntimeException('This user already exists');
        }

        // Create user
        $user = new User();
        $user->setName($data['name']);
        $user->setPhone($data['phone']);
        $user->setCity($data['city']);
        $user->setColor($data['color']);
        $user->setEmail($data['email']);

        // Validate and set roles
         if (isset($data['role'])) {
            $allowedRoles = array_column(RoleUser::cases(), 'value');
            foreach ($data['role'] as $r) {
                if (!in_array($r, $allowedRoles)) {
                    throw new \InvalidArgumentException(
                        'Invalid role: ' . $r . '. Allowed roles: ' . implode(', ', $allowedRoles)
                    );
                }
            }
            $user->setRoles($data['role']);
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $this->em->persist($user);
        $this->em->flush();

        $this->logEntryService->createLogEntry('User created: ' . $user->getName());

        return [
            'id'    => $user->getId(),
            'name'  => $user->getName(),
            'phone' => $user->getPhone(),
            'city'  => $user->getCity(),
            'color' => $user->getColor(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];
    }

    // List users
    public function listUsers(): array
    {
        $users = $this->userrepo->findAll();
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id'    => $user->getId(),
                'name'  => $user->getName(),
                'phone' => $user->getPhone(),
                'city'  => $user->getCity(),
                'color' => $user->getColor(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }
        return $data;
    }

    // Update user
    public function updateUsers(Request $request, int $id): array
    {
        $user = $this->userrepo->find($id);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $data = json_decode($request->getContent(), true);

        // Validate email if provided
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('The email is not valid');
            }
            $userExists = $this->userrepo->findOneBy(['email' => $data['email']]);
            if ($userExists && $userExists->getId() !== $user->getId()) {
                throw new \RuntimeException('This email is already used by another user');
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['name']))  $user->setName($data['name']);
        if (isset($data['phone'])) $user->setPhone($data['phone']);
        if (isset($data['city']))  $user->setCity($data['city']);
        if (isset($data['color'])) $user->setColor($data['color']);

        // Validate and update roles if provided
        if (isset($data['role'])) {
            $allowedRoles = array_column(RoleUser::cases(), 'value');
            foreach ($data['role'] as $r) {
                if (!in_array($r, $allowedRoles)) {
                    throw new \InvalidArgumentException(
                        'Invalid role: ' . $r . '. Allowed roles: ' . implode(', ', $allowedRoles)
                    );
                }
            }
            $user->setRoles($data['role']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }

        $this->em->flush();

        $this->logEntryService->createLogEntry('User updated: ' . $user->getName());

        return ['status' => 'User updated successfully'];
    }

    // Delete user
    public function deleteUsers(int $id): array
    {
        $user = $this->userrepo->find($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->em->remove($user);
        $this->em->flush();

        $this->logEntryService->createLogEntry('User deleted: ' . $user->getName());

        return ['message' => 'User deleted successfully'];
    }
}