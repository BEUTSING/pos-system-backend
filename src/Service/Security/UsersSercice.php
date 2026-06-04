<?php
// ─────────────────────────────────────────────────────────────────────────────
// FILE: UsersSercice.php
// CORRECTIONS:
//   1. REMOVED dd($company) — this was blocking all user creation (500 error)
//   2. User is now assigned to the ACTIVE company (from X-Company-Id header)
//      not just the first company found for this admin
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Service\Security;

use App\Entity\Security\User;
use App\Enum\RoleUser;
use App\Repository\Security\UserRepository;
use App\Service\Company\CompanyService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersSercice
{
    private $em;
    private $passwordHasher;
    private $userrepo;
    private $companyService;

    public function __construct(
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository              $userrepo,
        CompanyService              $companyService
    ) {
        $this->em             = $em;
        $this->passwordHasher = $passwordHasher;
        $this->userrepo       = $userrepo;
        $this->companyService = $companyService;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // createUsers — create a new user and assign them to the ACTIVE company
    //
    // The active company is determined by CompanyService::getCurrentCompany()
    // which reads the X-Company-Id header sent by Flutter.
    // This means if the admin has 2 restaurants and activated "chez-bk",
    // the new user will be assigned to "chez-bk" automatically.
    // ─────────────────────────────────────────────────────────────────────────
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

        // Get the ACTIVE company from X-Company-Id header (sent by Flutter)
        // CompanyService reads the header and verifies ownership
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException(
                'No active company found. Please activate a restaurant first.'
            );
        }

        // ── REMOVED: dd($company) was here — it was causing 500 errors ──────

        // Create the new user
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
                        'Invalid role: ' . $r . '. Allowed: ' . implode(', ', $allowedRoles)
                    );
                }
            }
            $user->setRoles($data['role']);
        }

        // Assign the user to the ACTIVE company
        // This is the key fix: user gets the company that is currently active
        // in Flutter (the one the admin double-clicked on in company_page)
        $user->setCompany($company);

        // Hash the password before storing
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        $this->em->persist($user);
        $this->em->flush();

        return [
            'id'      => $user->getId(),
            'name'    => $user->getName(),
            'phone'   => $user->getPhone(),
            'city'    => $user->getCity(),
            'color'   => $user->getColor(),
            'email'   => $user->getEmail(),
            'roles'   => $user->getRoles(),
            // Return the company name so Flutter can confirm it
            'company' => $user->getCompany()?->getNameComp(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // listUsers — return only users belonging to the ACTIVE company
    // ─────────────────────────────────────────────────────────────────────────
    public function listUsers(): array
    {
        // Get the active company from X-Company-Id header
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No active company found');
        }

        // Filter by active company only — not findAll()
        $users = $this->userrepo->findBy(['company' => $company]);

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id'      => $user->getId(),
                'name'    => $user->getName(),
                'phone'   => $user->getPhone(),
                'city'    => $user->getCity(),
                'color'   => $user->getColor(),
                'email'   => $user->getEmail(),
                'roles'   => $user->getRoles(),
                'company' => $user->getCompany()?->getNameComp(),
            ];
        }
        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // updateUsers — update an existing user
    // ─────────────────────────────────────────────────────────────────────────
    public function updateUsers(Request $request, int $id): array
    {
        $user = $this->userrepo->find($id);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $data = json_decode($request->getContent(), true);

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

        if (isset($data['role'])) {
            $allowedRoles = array_column(RoleUser::cases(), 'value');
            foreach ($data['role'] as $r) {
                if (!in_array($r, $allowedRoles)) {
                    throw new \InvalidArgumentException(
                        'Invalid role: ' . $r . '. Allowed: ' . implode(', ', $allowedRoles)
                    );
                }
            }
            $user->setRoles($data['role']);
        }

        if (isset($data['password'])) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
        }

        $this->em->flush();

        return ['status' => 'User updated successfully'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // deleteUsers — permanently remove a user
    // ─────────────────────────────────────────────────────────────────────────
    public function deleteUsers(int $id): array
    {
        $user = $this->userrepo->find($id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->em->remove($user);
        $this->em->flush();

        return ['message' => 'User deleted successfully'];
    }
}