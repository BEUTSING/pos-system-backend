<?php

namespace App\Service\Checkout;

use App\Entity\Checkout\Cancellation;
use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\OrderItem;
use App\Entity\Checkout\Sale;
use App\Enum\SaleStatut;
use App\Repository\Checkout\CancellationRepository;
use App\Repository\Checkout\CustomerOrderRepository;
use App\Repository\Checkout\OrderItemRepository;
use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use App\Service\LogEntryService;
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
    private $logEntryService;

    public function __construct(ProductRepository $productRepository, 
                                EntityManagerInterface $entityManager,
                                Security $security,
                                CustomerOrderRepository $customerorderrepo,CancellationRepository $cancellationrepo,
                                SaleRepository $sale_repository,OrderItemRepository $orderItemrepo,LogEntryService $logEntryService)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->security=$security;
        $this->customerorderrepo=$customerorderrepo;
        $this->saleRepo = $sale_repository;
        $this->orderItemrepo=$orderItemrepo;
        $this->cancellationrepo=$cancellationrepo;
        $this->logEntryService = $logEntryService;
    }
    
    public function processOrder(Request $request)
{
    $data = json_decode($request->getContent(), true);

    $user = $this->security->getUser();
    $isNewOrder = false;
    // Vérifier si c'est une nouvelle commande ou une commande existante
    if (isset($data['customerOrderId'])) {
        $customerOrder = $this->customerorderrepo->find($data['customerOrderId']);
        if (!$customerOrder) {
            throw new \Exception('Customer order not found');
        }
    } else {
        $customerOrder = new CustomerOrder();
        $customerOrder->setWaiter($user);
        $this->entityManager->persist($customerOrder);
        $isNewOrder=true; // Indicate that this is a new order
    }

    $items = $data['items'] ?? [];
    if (empty($items)) {
        throw new \Exception("No items provided");
    }

    foreach ($items as $item) {
        $product = $this->productRepository->find($item['product_id']);
        if (!$product) {
            throw new \Exception('Product not found');
        }

        if ($item['quantity'] > $product->getQuantity()) {
            throw new \Exception('The quantity in stock is insufficient');
        }

        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($item['quantity']);
        $orderItem->setPrice($product->getSaleprice());

        $this->entityManager->persist($orderItem);
        $customerOrder->addOrderItem($orderItem);

        // Mise à jour du stock
        $product->setQuantity($product->getQuantity() - $orderItem->getQuantity());
        $this->entityManager->persist($product);
    }

    // Sauvegarde en BDD
    $this->entityManager->persist($customerOrder);
    $this->entityManager->flush();

    // Calcul du total
    $totalAmount = 0;
    foreach ($customerOrder->getOrderItems() as $orderItem) {
        $totalAmount += $orderItem->getPrice() * $orderItem->getQuantity();
    }
//log the order creation or update this order
    if ($isNewOrder) {
    $this->logEntryService->createLogEntry(
        sprintf("New order created (ID %d) by %s", $customerOrder->getId(), $user->getUserIdentifier())
    );
} else {
    $this->logEntryService->createLogEntry(
        sprintf("Item(s) added to order (ID %d) by user ID %s", $customerOrder->getId(), $user->getUserIdentifier())
    );
}
    // Réponse JSON
    return [
        "message" => isset($data['customerOrderId']) ? "Items added successfully" : "Order created successfully",
        "order_id" => $customerOrder->getId(),
        "waiter_id" => $customerOrder->getWaiter()->getId(),
        "total_amount" => $totalAmount,
        "items" => $customerOrder->getOrderItems()->map(function (OrderItem $orderItem) {
            return [
                "order_item_id" => $orderItem->getId(),
                "product_id" => $orderItem->getProduct()->getId(),
                "product_name" => $orderItem->getProduct()->getProductname(),
                "quantity" => $orderItem->getQuantity(),
                "price" => $orderItem->getPrice(),
                "subtotal" => $orderItem->getPrice() * $orderItem->getQuantity(),
                "date_create" => $orderItem->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update" => $orderItem->getUpdatedAt()->format("Y-m-d H:i:s"),
            ];
        })->toArray()
    ];

}

public function createSaleFromOrder(Request $request){

    $data= json_decode($request->getContent(),true);

    $customerOrder=$this->customerorderrepo->find($data['customerOrderId']);
   
    $saleExists = $this->saleRepo->findOneBy(["customerOrder" => $customerOrder]);
       $totalAmount=0;
     foreach($customerOrder->getOrderItems() as $orderitem){

        $totalAmount= $totalAmount+($orderitem->getPrice()*$orderitem->getQuantity());
     }


    if($saleExists){
        $data=[
            "sale_id" => $saleExists->getId(),
            "teller" => $saleExists->getTeller()->getName(),
            "order_id" => $customerOrder->getId(),
            "total_amount" => $totalAmount,
            "payment_method" => $saleExists->getPaymentMethod(),
            "items" => $customerOrder->getOrderItems()->map(function (OrderItem $item) {

                return [
                "product_id"=>$item->getId(),
                "product_name"=>$item->getProduct()->getProductname(),
                "quantity"=>$item->getQuantity(),
                "price"=>$item->getPrice(),
                "subtotal" => $item->getPrice() * $item->getQuantity(),
                "date_create"=>$item->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update"=>$item->getUpdatedAt()->format("Y-m-d H:i:s"),
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


     $this->entityManager->persist($sale);
     $this->entityManager->flush();

     //log the sale creation
     $this->logEntryService->createLogEntry(
        sprintf("Sale created (ID %d) by user ID %s", $sale->getId(), $user->getUserIdentifier())
    );  

$data=[
            "sale_id" => $sale->getId(),
            "statut"=>$sale->getStatut(),
            "teller" => $sale->getTeller()->getName(),
            "order_id" => $customerOrder->getId(),
            "total_amount" => $totalAmount,
            "payment_method" => $sale->getPaymentMethod(),
            "items" => $customerOrder->getOrderItems()->map(function (OrderItem $item) {

                return [
                "product_id"=>$item->getId(),
                "product_name"=>$item->getProduct()->getProductname(),
                "quantity"=>$item->getQuantity(),
                "price"=>$item->getPrice(),
                "subtotal" => $item->getPrice() * $item->getQuantity(),
                "date_create"=>$item->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update"=>$item->getUpdatedAt()->format("Y-m-d H:i:s"),
                ];
            })->toArray()
];
return $data;

}

public function orderItemCancellation(Request $request){
        
    $data= json_decode($request->getContent(), true);
    $quantity=$data['quantity']?? null;
    $user=$this->security->getUser();


    if($quantity<=0){
        throw new \Exception("Quantity must be greater than zero");
    }
    $orderitem=$this->orderItemrepo->find($data['orderItemId']);
    if (!$orderitem) {
    throw new \Exception('The order item was not found.');
}

    $product=$orderitem->getProduct();
    $customerOrder = $orderitem->getCustomerOrder();


    if($quantity){

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
    
 
            $orderitem->setQuantity($quantity);
            $message = 'OrderItem quantity has been updated.';
            $this->entityManager->persist($orderitem);

        //persit
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Log the order item update
                $this->logEntryService->createLogEntry(
        sprintf(
        "Quantity of item '%s' (ID %d) in order ID %d updated from %d to %d by user ID %s",$product->getProductname(),$orderitem->getId(),
        $customerOrder->getId(),$currentQuantity,$quantity,$user->getUserIdentifier())
        );

        $data[]=[
            'itemid'=> $orderitem->getId(),
            'orderid'=> $customerOrder->getId(),
            'message' => $message,
            "current_quantity"=>$currentQuantity,
            'items'=>$customerOrder->getOrderItems()->map(function(OrderItem $orderitem)
        {
            return 
            [
                "product_id"=>$orderitem->getId(),
                "product_name"=>$orderitem->getProduct()->getProductname(),
                "price"=>$orderitem->getPrice(),
                "new_quantity_item"=>$orderitem->getQuantity(),
                "date_create"=>$orderitem->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update"=>$orderitem->getUpdatedAt()->format("Y-m-d H:i:s"),
            ];

        })->toArray()
    ];
    return $data;
 }else{

   $product->setQuantity($product->getQuantity() + $orderitem->getQuantity());
        $customerOrder->removeOrderItem($orderitem);
        $this->entityManager->remove($orderitem);
        $this->entityManager->persist($product);

        // Log the order item removal
        $this->logEntryService->createLogEntry(
            sprintf("OrderItem (ID %d) removed by user ID %s", $orderitem->getId(), $user->getUserIdentifier())
        );
 }
        $orderIsEmpty = $customerOrder->getOrderItems()->isEmpty();
        if ($orderIsEmpty) {
            $idcustomerorder = $customerOrder->getId();
            $waiterId = $customerOrder->getWaiter()->getId();
            $this->entityManager->remove($customerOrder);
            //LogEntry for deleted order
            $this->logEntryService->createLogEntry(
            sprintf(
            "Order ID %d deleted because empty, initiated by user ID %",
            $idcustomerorder,
            $customerOrder->getWaiter()->getId()));
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
                "date_create"=>$orderitem->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update"=>$orderitem->getUpdatedAt()->format("Y-m-d H:i:s"),
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
    
        // Log the cancellation
        $this->logEntryService->createLogEntry(
            sprintf("Sale (ID %d) cancelled by user ID %s for reason: %s", 
            $sale->getId(),$user->getUserIdentifier(),$reason)
        );
       
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
                "date_create"=>$item->getCreatedAt()->format("Y-m-d H:i:s"),
                "date_update"=>$item->getUpdatedAt()->format("Y-m-d H:i:s"),
                ];
            })->toArray()
];
return $data;
}
}