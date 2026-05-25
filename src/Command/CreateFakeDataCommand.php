<?php

namespace App\Command;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Entity\Checkout\Sale;
use App\Entity\Company\Company;
use App\Entity\Product\Category;
use App\Entity\Product\Product;
use App\Entity\Security\User;
use App\Entity\Stock\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateFakeDataCommand extends Command
{
    protected static $defaultName = 'app:create-fake-data';

    private const RESTAURANTS = [
        [
            'name' => 'Le Gourmet Restaurant',
            'email' => 'contact@legourmet.cm',
            'phone' => '+237670000001',
            'city' => 'Douala',
            'employees' => 30,
            'products' => ['Filet de Boeuf', 'Poulet Rôti', 'Saumon Grillé', 'Salade César', 'Steak Frites', 'Pâtes Carbonara', 'Risotto aux Champignons'],
            'categories' => ['Viandes', 'Poissons', 'Salades', 'Pâtes', 'Risotto'],
        ],
        [
            'name' => 'Chez Marie Restaurant',
            'email' => 'contact@chezmarie.cm',
            'phone' => '+237670000002',
            'city' => 'Yaoundé',
            'employees' => 25,
            'products' => ['Boeuf Bourguignon', 'Coq au Vin', 'Ratatouille', 'Tarte Tatin', 'Crème Brûlée', 'Soupe à l\'Oignon', 'Quiche Lorraine'],
            'categories' => ['Plats Traditionnels', 'Soupes', 'Desserts', 'Tartes', 'Entrées'],
        ],
        [
            'name' => 'Pizza Bella Italia',
            'email' => 'contact@pizzabella.cm',
            'phone' => '+237670000003',
            'city' => 'Garoua',
            'employees' => 20,
            'products' => ['Pizza Margherita', 'Pizza Pepperoni', 'Pizza Quatre Fromages', 'Lasagne', 'Spaghetti Bolognese', 'Tiramisu', 'Panna Cotta'],
            'categories' => ['Pizzas', 'Pâtes', 'Desserts Italiens', 'Antipasti'],
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-fake-data')
            ->setDescription('Creates multiple fake restaurants with admins, users, products, categories, suppliers, orders and sales')
            ->addArgument('count', InputArgument::OPTIONAL, 'Number of restaurants to create', 3)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $restaurantCount = (int) $input->getArgument('count');
        
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $io->section('Creating restaurants with data...');
            
            for ($r = 0; $r < $restaurantCount; $r++) {
                $restaurantData = self::RESTAURANTS[$r % count(self::RESTAURANTS)];
                
                $io->text("Creating restaurant: {$restaurantData['name']}");
                
                // Create Admin User
                $admin = $this->createAdmin(
                    "admin{$r}@{$restaurantData['name']}.cm",
                    'password',
                    $restaurantData['city']
                );
                $io->success("  ✓ Admin user created: {$admin->getEmail()}");

                // Create Company (Restaurant)
                $company = $this->createCompany($admin, $restaurantData);
                $io->success("  ✓ Restaurant created: {$company->getNameComp()}");

                // Update admin with company
                $admin->setCompany($company);
                $this->entityManager->persist($admin);
                $this->entityManager->flush();

                // Create Suppliers
                $suppliers = $this->createSuppliers($company, 4);
                $io->success("  ✓ " . count($suppliers) . " suppliers created");

                // Create Categories
                $categories = $this->createCategories($company, $restaurantData['categories']);
                $io->success("  ✓ " . count($categories) . " categories created");

                // Create Products
                $products = $this->createProducts($company, $categories, $suppliers, $restaurantData['products']);
                $io->success("  ✓ " . count($products) . " products created");

                // Create additional Users (employees)
                $users = $this->createUsers($company, $admin, 5);
                $io->success("  ✓ " . count($users) . " additional users created");

                // Create Orders and Sales
                $this->createOrdersAndSales($company, $users, $products, 10);
                $io->success("  ✓ Orders and sales created");
            }
            
            $this->entityManager->getConnection()->commit();
            $io->success(sprintf('%d restaurants created successfully!', $restaurantCount));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createAdmin(string $email, string $password, string $city): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setColor('#FF6B6B');
        $user->setPhone('+2376' . rand(10000000, 99999999));
        $user->setCity($city);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createCompany(User $owner, array $data): Company
    {
        $company = new Company();
        $company->setNameComp($data['name']);
        $company->setEmailComp($data['email']);
        $company->setPhone($data['phone']);
        $company->setCity($data['city']);
        $company->setNumEmpl($data['employees']);
        $company->setSiteWeb('https://' . strtolower(str_replace(' ', '', $data['name'])) . '.cm');
        $company->setOwner($owner);
        
        $this->entityManager->persist($company);
        $this->entityManager->flush();
        
        return $company;
    }

    private function createSuppliers(Company $company, int $count): array
    {
        $suppliers = [];
        $supplierNames = ['Global Supply Co', 'Fresh Food Ltd', 'Local Distributors', 'Quality Ingredients Inc'];
        
        for ($i = 0; $i < $count; $i++) {
            $supplier = new Supplier();
            $supplier->setName($supplierNames[$i]);
            $supplier->setPhone('+2376' . rand(10000000, 99999999));
            $supplier->setCity(['Yaoundé', 'Douala', 'Garoua', 'Bamenda', 'Bafoussam'][rand(0, 4)]);
            $supplier->setEmail('supplier' . ($i + 1) . '@example.com');
            $supplier->setCompany($company);
            
            $this->entityManager->persist($supplier);
            $suppliers[] = $supplier;
        }
        
        $this->entityManager->flush();
        return $suppliers;
    }

    private function createCategories(Company $company, array $categoryNames): array
    {
        $categories = [];
        
        foreach ($categoryNames as $index => $categoryName) {
            $category = new Category();
            $category->setCategoryname($categoryName);
            $category->setDescription("Category for {$categoryName}");
            $category->setCompany($company);
            
            $this->entityManager->persist($category);
            $categories[] = $category;
        }
        
        $this->entityManager->flush();
        return $categories;
    }

    private function createProducts(Company $company, array $categories, array $suppliers, array $productNames): array
    {
        $products = [];
        
        foreach ($productNames as $name) {
            $product = new Product();
            $product->setProductname($name);
            $product->setPurchaseprice((string) rand(1000, 50000));
            $product->setSaleprice((string) rand(1500, 60000));
            $product->setQuantity(rand(10, 100));
            $product->setMinimumstock(rand(5, 20));
            $product->setCategory($categories[array_rand($categories)]);
            $product->setSupplier($suppliers[array_rand($suppliers)]);
            $product->setCompany($company);
            
            $this->entityManager->persist($product);
            $products[] = $product;
        }
        
        $this->entityManager->flush();
        return $products;
    }

    private function createUsers(Company $company, User $admin, int $count): array
    {
        $users = [];
        $names = ['Jean Dupont', 'Marie Claire', 'Paul Martin', 'Sophie Bernard', 'Lucas Petit', 'Emma Wilson', 'Noah Garcia'];
        $roles = ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_CASHIER'];
        $cities = ['Douala', 'Yaoundé', 'Garoua', 'Bamenda', 'Bafoussam'];
        
        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setEmail('user' . $company->getId() . '_' . ($i + 1) . '@example.com');
            $user->setRoles([$roles[array_rand($roles)]]);
            $user->setColor(['#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'][array_rand(range(0, 4))]);
            $user->setPhone('+2376' . rand(10000000, 99999999));
            $user->setCity($cities[array_rand($cities)]);
            $user->setName($names[array_rand($names)]);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password' . ($i + 1)));
            $user->setCompany($company);
            
            $this->entityManager->persist($user);
            $users[] = $user;
        }
        
        $this->entityManager->flush();
        return $users;
    }

    private function createOrdersAndSales(Company $company, array $users, array $products, int $count): void
    {
        $paymentMethods = ['Cash', 'Credit Card', 'Mobile Money'];
        $statuses = ['completed', 'pending'];
        
        for ($i = 0; $i < $count; $i++) {
            $waiter = $users[array_rand($users)];
            $teller = $users[array_rand($users)];
            
            $order = new CustomerOrder();
            $order->setWaiter($waiter);
            $order->setCompany($company);
            
            $itemCount = rand(1, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $orderItem = new OrderItem();
                $product = $products[array_rand($products)];
                $quantity = rand(1, 3);
                
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setPrice((float) $product->getSaleprice());
                $orderItem->setCustomerOrder($order);
                $orderItem->setCompany($company);
                
                $this->entityManager->persist($orderItem);
            }
            
            $this->entityManager->persist($order);
            
            $sale = new Sale();
            $sale->setCustomerOrder($order);
            $sale->setTeller($teller);
            $sale->setIsPaid(rand(0, 1) === 1);
            $sale->setPaymentMethod($paymentMethods[array_rand($paymentMethods)]);
            $sale->setStatut($statuses[array_rand($statuses)]);
            $sale->setCompany($company);
            
            $this->entityManager->persist($sale);
            $order->setSale($sale);
        }
        
        $this->entityManager->flush();
    }
}