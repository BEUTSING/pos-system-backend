<?php

namespace App\Controller\product;

use App\Entity\Product\Product;
use App\Repository\Checkout\ShelfRepository;
use App\Repository\Product\CategoryRepository;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
   
    #[Route('/product/search/{pname}', name: 'app_product_search', methods: ['GET'])]
    public function search(ProductRepository $repo, string $pname): JsonResponse
    {
        $products = $repo->findBy(['productname' => $pname]);
        if (!$products) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($products, Response::HTTP_OK);
    }

    #[Route('/product', name: 'app_product_display', methods: ['GET'])]
    public function display(ProductRepository $repos): JsonResponse
    {
       return $this->json($repos->findAll(), Response::HTTP_OK);

    }

    #[Route('/product', name: 'app_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em,CategoryRepository $categoryrepository, SupplierRepository $supplierrepository): JsonResponse
    {
     $data= json_decode($request->getContent(), true);

        $category = $categoryrepository->find($data['category']);
        if(!$category){
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }
         $supplier = $supplierrepository->find($data['supplier']);
        if(!$supplier){
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $product = new Product();
        $product->setProductname($data['productname']);
        $product->setCategory($category);
        $product->setSupplier($data['supplier']);
        $product->setSaleprice($data['saleprice']);
        $product->setPurchaseprice($data['purchaseprice']);
        $product->setQuantity($data['quantity']);

        $product->setMinimumstock($data['minimumstock']);
         $em->persist($product);
        $em->flush();

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
            
         if(isset($data['supplier'])){
            $supplier = $supplierrepository->find($data['name']);
            if (!$category) {
                return $this->json(['error' => 'sypplier not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($supplier);
        }
        $product->setProductname($data['productname'] ?? $product->getProductname());
        $product->setSupplier($data['supplier'] ?? $product->getSupplier());
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
