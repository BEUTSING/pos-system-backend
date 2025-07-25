<?php

namespace App\Controller\product;

use App\Entity\Product\Product;
use App\Repository\Product\CategoryRepository;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\SupplierRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }

    #[Route('/product/search/{pname}', name: 'app_product_search', methods: ['GET'])]
    public function search(ProductRepository $repo, string $pname): JsonResponse
    {
        $products = $repo->findProduct($pname);
        if (!$products) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
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
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/product', name: 'app_product_display', methods: ['GET'])]
    public function display(ProductRepository $repos): JsonResponse
    {
       $products = $repos->findAll();
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
        return $this->json($data, Response::HTTP_OK);

    }

    #[Route('/product', name: 'app_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em,CategoryRepository $categoryrepository, SupplierRepository $supplierrepository): JsonResponse
    {
     $data= json_decode($request->getContent(), true);

     if(!isset($data['productname']) || !isset($data['category']) || !isset($data['saleprice']) || !isset($data['purchaseprice']) || !isset($data['quantity']) || !isset($data['minimumstock'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryrepository->find($data['category']);
        if(!$category){
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $supplier = $data['supplier']? $supplierrepository->find($data['supplier']) : null;


        $product = new Product();
        $product->setProductname($data['productname']);
        $product->setCategory($category);
        $product->setSupplier($supplier);
        $product->setSaleprice($data['saleprice']);
        $product->setPurchaseprice($data['purchaseprice']);
        $product->setQuantity($data['quantity']);
        $product->setMinimumstock($data['minimumstock']);

         $em->persist($product);
        $em->flush();
        $this->logEntryService->createLogEntry('Product created: ' . $product->getProductname());

        return $this->json(['message' => 'Product created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/product/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function update(Product $product, Request $request, EntityManagerInterface $em,CategoryRepository $categoryrepository,SupplierRepository $supplierrepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(isset($data['category'])){
            $category = $categoryrepository->find($data['category']);
            if (!$category) {
                return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        }

         if (isset($data['supplier'])) {
    $supplier = $supplierrepository->find($data['supplier']);
    if (!$supplier) {
        return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);

    }
               $product->setSupplier($supplier);
}

        $product->setProductname($data['productname'] ?? $product->getProductname());
        $product->setSaleprice($data['saleprice'] ?? $product->getSaleprice());
        $product->setPurchaseprice($data['purchaseprice'] ?? $product->getPurchaseprice());
        $product->setMinimumstock($data['minimumstock']?? $product->getMinimumstock());
        $product->setQuantity($data['quantity'] ?? $product->getQuantity());

        $em->flush();

        return $this->json(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    #[Route('/product/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }

}
