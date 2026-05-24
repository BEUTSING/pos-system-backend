<?php
namespace App\Service\Product;

use App\Entity\Product\Product;
use App\Repository\Product\CategoryRepository;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\SupplierRepository;
// use App\Service\CompanyService;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class ProductService{

        private $em;
        private $categoryrepository;
        private $supplierrepository;
        private $logEntryService;
        private $security;
        private $ProductRepository ;
        // private $companyService;
        public function __construct(LogEntryService $logEntryService,EntityManagerInterface $em,CategoryRepository $categoryrepository, SupplierRepository $supplierrepository,Security $security,ProductRepository $repo)
        {
        $this->security= $security;
        $this->logEntryService= $logEntryService;
        $this->ProductRepository=$repo;
        $this->em=$em;
        $this->categoryrepository=$categoryrepository;
        $this->supplierrepository=$supplierrepository;
        }

            //search
        public function searchP(string $pname): array
        {
        $products = $this->ProductRepository->findProduct($pname);
        if (!$products) {
            throw new \InvalidArgumentException('Product not found');
        }
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'productname' => $product->getProductname(),
                'category' => $product->getCategory() ? $product->getCategory()->getCategoryname() : null,
                'supplier' => $product->getSupplier() ? $product->getSupplier()->getName() : null,
                'saleprice' => $product->getSaleprice(),
                'purchaseprice' => $product->getPurchaseprice(),
                'quantity' => $product->getQuantity(),
                'minimumstock' => $product->getMinimumstock(),
            ];
        }
        return  $data;
        }


            //list
        public function listP():array
        {
            // $company= $this->companyService->currentCompany();
            //  $products =  $this->ProductRepository->findProductAll($company);
                         $products =  $this->ProductRepository->findAll();

                if (!$products) {
                    throw new RuntimeException('no products registered');
                }
                $data = [];

                foreach ($products as $product) {
                    $data[] = [
                        'id' => $product->getId(),
                        'productname' => $product->getProductname(),
                        'category' => $product->getCategory() ? $product->getCategory()->getCategoryname() : null,
                        'supplier' => $product->getSupplier() ? $product->getSupplier()->getName() : null,
                        'saleprice' => $product->getSaleprice(),
                        'purchaseprice' => $product->getPurchaseprice(),
                        'quantity' => $product->getQuantity(),
                        'minimumstock' => $product->getMinimumstock(),
                    ];
                }
                return $data;

    }
    
    //create
        public function createP(Request $request): array
        {
            $data= json_decode($request->getContent(), true);

            // Get the current company
            // $company= $this->companyService->currentCompany();

            // Validate required fields
            $required=['productname','category','saleprice','purchaseprice','quantity','minimumstock'];
            foreach($required as $field){
               if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("The field $field is required");
                }
                }

                $category = $this->categoryrepository->find($data['category']);
                if(!$category){
                throw new \RuntimeException('Category not found');
                }

                $supplier = $data['supplier']? $this->supplierrepository->find($data['supplier']) : null;


                $product = new Product();
                $product->setProductname($data['productname']);
                $product->setCategory($category);
                $product->setSupplier($supplier);
                $product->setSaleprice($data['saleprice']);
                $product->setPurchaseprice($data['purchaseprice']);
                $product->setQuantity($data['quantity']);
                $product->setMinimumstock($data['minimumstock']);
                // $product->setCompany($company);

                $this->em->persist($product);
                $this->em->flush();
                $data=[];
                $data[] = [
                'id' => $product->getId(),

                'productname' => $product->getProductname(),

                'category' => [
                    'id' => $product->getCategory()->getId(),
                    'name' => $product->getCategory()->getCategoryname(),
                    'description' => $product->getCategory()->getDescription(),
                ],

                'supplier' => $product->getSupplier() ? [
                    'id' => $product->getSupplier()->getId(),
                    'name' => $product->getSupplier()->getName(),
                ] : null, 
                'saleprice' => (float) $product->getSaleprice(),
                'purchaseprice' => (float) $product->getPurchaseprice(), 
                'quantity' => $product->getQuantity(),
                'minimumstock' => $product->getMinimumstock(), 
                'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
                $this->logEntryService->createLogEntry('Product created: ' . $product->getProductname(). " by");

                return $data;
            }


            public function updateP(int $id,Request $request): array
            {
                $data = json_decode($request->getContent(), true);

                $product = $this->ProductRepository->find($id);
                if (!$product) {
                    throw new \InvalidArgumentException('Product not found');
                }

                if(isset($data['category'])){
                    $category = $this->categoryrepository->find($data['category']);
                    if (!$category) {
                        throw new \InvalidArgumentException( 'Category not found' );
                    }
                    $product->setCategory($category);
                }

                if (isset($data['supplier'])) {
                $supplier = $this->supplierrepository->find($data['supplier']);
                if (!$supplier) {
                   throw new \InvalidArgumentException( 'Supplier not found' );

                }
                    $product->setSupplier($supplier);
        }
                if(isset($data['saleprice']) && $data['saleprice'] < 0){
                    throw new \InvalidArgumentException('Sale price must be a positive number');
                }
                if(isset($data['purchaseprice']) && $data['purchaseprice'] < 0){
                    throw new \InvalidArgumentException('Purchase price must be a positive number');
                }
                if(isset($data['productname'])){
                    $product->setProductname($data['productname']  );

                }
                if(isset($data['quantity'])){
                    $product->setQuantity($data['quantity']);
                }
                if(isset($data['minimumstock'])){
                    $product->setMinimumstock($data['minimumstock']);
                }
                if(isset($data['saleprice'])){
                    $product->setSaleprice($data['saleprice']);
                }
                if(isset($data['purchaseprice'])){
                    $product->setPurchaseprice($data['purchaseprice']);
                }

                
               $this->em->flush();

                $this->logEntryService->createLogEntry('Product updated: ' . $product->getProductname());
                return  ['message' => 'Product updated successfully'] ;
            }

            //delete
            public function deleteP(int $id): string
             {
                $product = $this->ProductRepository->find($id);
                if (!$product) {
                    throw new \InvalidArgumentException('Product not found');
                }
            
                $this->em->remove($product);
                $this->em->flush();
                
                $this->logEntryService->createLogEntry('Product deleted: ' . $product->getProductname());
                return  'Product deleted successfully' ;
            }

        }