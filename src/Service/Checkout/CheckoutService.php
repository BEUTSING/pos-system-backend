<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Repository\Product\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutService
{
    private $productRepository;
    private $entityManager;
    public function __construct(ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }
    // This service can be used to handle checkout-related logic
    // For example, processing orders, calculating totals, etc.

    public function processOrder(Request $request)
    {
      $data= json_decode($request->getContent(), true);
        // Here you can implement the logic to process the order
        $items= $data['items'] ?? [];
        $total = 0;
        $customerOrder = new CustomerOrder();
        foreach ($items as $item){
            $product = $this->productRepository->find($item['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }
            $orderitem = new OrderItem();
            $orderitem->setProduct($product);
            $orderitem->setQuantity($item['quantity']);
            $orderitem->setPrice($product->getSaleprice());
            $this->entityManager->persist($orderitem);
            $this->entityManager->flush();


            $customerOrder->addOrderItem($orderitem);

        
    }
    

    
    $this->entityManager->persist($customerOrder);
    $this->entityManager->flush();

    return "CustomerOrder created successfully !!";

}

}