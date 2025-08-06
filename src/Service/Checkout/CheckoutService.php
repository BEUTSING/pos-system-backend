<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\Cancellation;
use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Entity\Checkout\Sale;
use App\Enum\SaleStatus;
use App\Enum\SaleStatut;
use App\Repository\Checkout\CancellationRepository;
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
    private $cancellationrepo;
    public function __construct(ProductRepository $productRepository, 
                                EntityManagerInterface $entityManager,
                                Security $security,
                                CustomerOrderRepository $customerorderrepo,CancellationRepository $cancellationrepo,
                                SaleRepository $sale_repository,OrderItemRepository $orderItemrepo)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->security=$security;
        $this->customerorderrepo=$customerorderrepo;
        $this->saleRepo = $sale_repository;
        $this->orderItemrepo=$orderItemrepo;
        $this->cancellationrepo=$cancellationrepo;
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
            })->toArray(),
                    "invoice"=>"copy"

        ];
        return $data;
    }

    $sale= new Sale();
   
    $user=$this->security->getUser();
    $sale->setTeller($user);
    $sale->setStatut(SaleStatut::PENDING->value);

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
            "statut"=>$sale->getStatut(),
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
public function orderItemCancellation(Request $request){
        
    $data= json_decode($request->getContent(), true);
    $orderitem=$this->orderItemrepo->find($data['orderItemId']);
    $quantity=$data['quantity']?? null;
    $product=$orderitem->getProduct();
    $customerOrder = $orderitem->getCustomerOrder();

//     if(!$orderitem){
//             throw new \Exception('the quantity in stock is insufficient
// ');
//     }

    if($quantity !== null){

    $currentQuantity=$orderitem->getQuantity();

    if ($currentQuantity < $quantity) {
        $diff = $quantity - $currentQuantity;

        if ($product->getQuantity() < $diff) {
            throw new \Exception('the quantity in stock is insufficient');
        }

        $product->setQuantity($product->getQuantity() - $diff);

    } else {
        $diff = $currentQuantity - $quantity;
        $product->setQuantity($product->getQuantity() + $diff);
    }
    
    if($quantity<=0){
        $customerOrder->removeOrderItem($orderitem);
        $this->entityManager->remove($orderitem);
            $message = 'OrderItem has been fully cancelled and removed from the order.';
    } else{
            $orderitem->setQuantity($quantity);
            $message = 'OrderItem quantity has been updated.';
     }

        //persit
         $this->entityManager->persist($product);
        if ($quantity > 0) {
            $this->entityManager->persist($orderitem);
        }
        $data[]=[
            'itemid'=> $orderitem->getId(),
            'orderid'=> $customerOrder->getId(),
            'message' => $message,
            'items'=>$customerOrder->getOrderItems()->map(function(OrderItem $orderitem)
        {
            return 
            [
                "product_id"=>$orderitem->getId(),
                "Product_name"=>$orderitem->getProduct()->getProductname(),
                "Quantity"=>$orderitem->getQuantity(),
                "price"=>$orderitem->getPrice(),
                "new_quantity_item"=>$orderitem->getQuantity(),
            ];

        })->toArray()
    ];
    return $data;
 }else{

    $product->setQuantity($product->getQuantity()+$orderitem->getQuantity());
    $customerOrder = $orderitem->getCustomerOrder();
    $customerOrder->removeOrderItem($orderitem);
    $this->entityManager->remove($orderitem);
        $this->entityManager->persist($product);
    }


    $isEmpty = $customerOrder->getOrderItems()->isEmpty();

    if ($isEmpty) {
        $idcustomerorder = $customerOrder->getId();
        $waiterId = $customerOrder->getWaiter()->getId();
        $this->entityManager->remove($customerOrder);
    }

    $this->entityManager->flush();

    $data=[
        "id_custormerOrder"=> $idcustomerorder,
        "Waiter_id"=>$waiterId,
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

public function cancelSale(Request $request){

        $data= json_decode($request->getContent(), true);
        $sale=$this->saleRepo->find($data['saleId']);

        if(!$sale){
            throw new \Exception("sale not found");
        }
         $reason=$data['reason'];

        if ($sale->getCancellation() !== null) {
            throw new \Exception('This sale has already been cancelled.');
        }

        $user=$this->security->getUser();

        $customerOrder=$sale->getCustomerOrder();
        $orderItems=$customerOrder->getOrderItems();

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();

            $product->setQuantity($product->getQuantity() + $orderItem->getQuantity());

            $this->entityManager->persist($product);
        }

        $sale->setStatut(SaleStatut::CANCELLED->value);
        $this->entityManager->persist($sale);

        $cancellation= new Cancellation();
        $cancellation->setUser($user);
        $cancellation->setSale($sale); 
        $cancellation->setCancellationReason($reason);
        $this->entityManager->persist($cancellation);
        $this->entityManager->flush();
       
        $data=[

            "id_cancel"=>$cancellation->getId(),
            "cancelled_by" => $cancellation->getUser(),
            "reason" => $cancellation->getCancellationreason(),
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
}