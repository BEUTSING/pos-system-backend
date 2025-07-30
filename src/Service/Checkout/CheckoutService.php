<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Repository\Product\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

use function PHPUnit\Framework\throwException;

class CheckoutService
{
    private $productRepository;
    private $entityManager;
    private $security;
    public function __construct(ProductRepository $productRepository, EntityManagerInterface $entityManager,Security $security)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->security=$security;
    }
    
    public function processOrder(Request $request)
    {
      $data= json_decode($request->getContent(), true);
        // Here you can implement the logic to process the order
        $items= $data['items'] ?? [];
        $total = 0;
        $customerOrder = new CustomerOrder();
        $user=$this->security->getUser();
        $customerOrder->setWaiter($user);
        foreach ($items as $item){
            $product = $this->productRepository->find($item['product_id']);
            if($item['quantity'] > $product->getQuantity())
                throw new \Exception('the quantity in stock is insufficient');

            if (!$product) {
                throw new \Exception('Product not found');
            }
            $orderitem = new OrderItem();
            $orderitem->setProduct($product);
            $orderitem->setQuantity($item['quantity']);
            $orderitem->setPrice($product->getSaleprice());
            

            $this->entityManager->persist($orderitem);

            $customerOrder->addOrderItem($orderitem);

            $product->setQuantity($product->getQuantity()-$orderitem->getQuantity());
            $this->entityManager->persist($product);


    }
    
    $this->entityManager->persist($customerOrder);
    $this->entityManager->flush();

    $data=[
        "id"=> $customerOrder->getId(),
        "Waiter_id"=>$customerOrder->getWaiter()->getId(),
        "Items"=>$customerOrder->getOrderItems()->map(function(OrderItem $orderitem)
        {
            return 
            [
                "Product"=>$orderitem->getProduct()->getProductname(),
                "Quantity"=>$orderitem->getQuantity(),
                "price"=>$orderitem->getPrice(),

            ];

        })->toArray()
        
    ];
    return $data;

}

}