<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Entity\Checkout\Sale;
use App\Repository\Checkout\CustomerOrderRepository;
use App\Repository\Checkout\OrderItemRepository;
use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class CheckoutService
{
    private $productRepository;
    private $entityManager;
    private $security;
    private $customerorderrepo;
    private $saleRepo;
    private $orderItemrepo;
    public function __construct(ProductRepository $productRepository, 
                                EntityManagerInterface $entityManager,
                                Security $security,
                                CustomerOrderRepository $customerorderrepo,
                                SaleRepository $sale_repository,OrderItemRepository $orderItemrepo)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->security=$security;
        $this->customerorderrepo=$customerorderrepo;
        $this->saleRepo = $sale_repository;
        $this->orderItemrepo=$orderItemrepo;
    }
    
    public function processOrder(Request $request)
    {
      $data= json_decode($request->getContent(), true);
        // Here you can implement the logic to process the order
        $items= $data['items'];
        $total = 0;
        $customerOrder = new CustomerOrder();
        $user=$this->security->getUser();
        $customerOrder->setWaiter($user);
        foreach ($items as $item){
            $product = $this->productRepository->find($item['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            if($item['quantity'] > $product->getQuantity())
                throw new \Exception('the quantity in stock is insufficient');

            
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
                "product_id"=>$orderitem->getId(),
                "Product_name"=>$orderitem->getProduct()->getProductname(),
                "Quantity"=>$orderitem->getQuantity(),
                "price"=>$orderitem->getPrice(),

            ];

        })->toArray()
        
    ];
    return $data;
}

public function createSaleFromOrder(Request $request){

    $data= json_decode($request->getContent(),true);

    $customerOrder=$this->customerorderrepo->find($data['customerOrderId']);
   
    $saleExists = $this->saleRepo->findOneBy(["customerOrder" => $customerOrder]);

    if($saleExists){
        $data=[
            "sale_id" => $saleExists->getId(),
            "teller" => $saleExists->getTeller()->getName(),
            "order_id" => $customerOrder->getId(),
            "total_amount" => $saleExists->getTotalAmount(),
            "payment_method" => $saleExists->getPaymentMethod(),
            "items" => $customerOrder->getOrderItems()->map(function (OrderItem $item) {

                return [
                "product_id"=>$item->getId(),
                "product_name"=>$item->getProduct()->getProductname(),
                "quantity"=>$item->getQuantity(),
                "price"=>$item->getPrice(),
                "subtotal" => $item->getPrice() * $item->getQuantity(),
                ];
            })->toArray()
        ];
        return $data;
    }

    $sale= new Sale();
   
    $user=$this->security->getUser();
    $sale->setTeller($user);

    $sale-> setCustomerOrder($customerOrder);
    $sale-> setPaymentMethod($data['paymentMethod']);
    $sale-> setIsPaid(false);

    $totalAmount=0;
     foreach($customerOrder->getOrderItems() as $orderitem){

        $totalAmount= $totalAmount+($orderitem->getPrice()*$orderitem->getQuantity());
     }
     $sale->setTotalAmount($totalAmount);

     $this->entityManager->persist($sale);
     $this->entityManager->flush();

$data=[
            "sale_id" => $sale->getId(),
            "teller" => $sale->getTeller()->getName(),
            "order_id" => $customerOrder->getId(),
            "total_amount" => $sale->getTotalAmount(),
            "payment_method" => $sale->getPaymentMethod(),
            "items" => $customerOrder->getOrderItems()->map(function (OrderItem $item) {

                return [
                "product_id"=>$item->getId(),
                "product_name"=>$item->getProduct()->getProductname(),
                "quantity"=>$item->getQuantity(),
                "price"=>$item->getPrice(),
                "subtotal" => $item->getPrice() * $item->getQuantity(),
                ];
            })->toArray()
];
return $data;

}
public function orderItemCanceletion(Request $request){
        
    $data= json_decode($request->getContent(), true);
    $orderitem=$this->orderItemrepo->find($data['orderItemId']);


    if(!$orderitem){
            throw new \Exception('Item not found');
    }

    $customerOrder = $orderitem->getCustomerOrder();

    $product=$orderitem->getProduct();
    $product->setQuantity($product->getQuantity()+$orderitem->getQuantity());

    $customerOrder->removeOrderItem($orderitem);
    $this->entityManager->persist($customerOrder);
    $this->entityManager->persist($product);
    
    $this->entityManager->flush();


    $data=[
        "id"=> $customerOrder->getId(),
        "Waiter_id"=>$customerOrder->getWaiter()->getId(),
        "Items"=>$customerOrder->getOrderItems()->map(function(OrderItem $orderitem)
        {
            return 
            [
                "product_id"=>$orderitem->getId(),
                "Product_name"=>$orderitem->getProduct()->getProductname(),
                "Quantity"=>$orderitem->getQuantity(),
                "price"=>$orderitem->getPrice(),

            ];

        })->toArray()
        
    ];
    return $data;
}

public function salecancel(Request $request){

        $data= json_decode($request->getContent(), true);
        $orderitem=$this->customerorderrepo->find($data['orderItemId']);



}

}
