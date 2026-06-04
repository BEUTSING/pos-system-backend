<?php
// ─────────────────────────────────────────────────────────────────────────────
// FILE: CheckoutService.php
// CORRECTION: Added CompanyService injection and setCompany() on CustomerOrder
//
// ROOT CAUSE OF ERROR:
//   SQLSTATE[23000]: Column 'company_id' cannot be null
//   → CustomerOrder entity has a NOT NULL company_id column
//   → But we never called $customerOrder->setCompany() before persisting
//
// FIX:
//   1. Inject CompanyService in constructor
//   2. Call $customerOrder->setCompany($company) when creating a new order
// ─────────────────────────────────────────────────────────────────────────────

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
use App\Service\Company\CompanyService; // ← ADDED: import CompanyService

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
    private $companyService; // ← ADDED: property for CompanyService

    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        CustomerOrderRepository $customerorderrepo,
        CancellationRepository $cancellationrepo,
        SaleRepository $sale_repository,
        OrderItemRepository $orderItemrepo,
        CompanyService $companyService // ← ADDED: inject CompanyService
    ) {
        $this->productRepository  = $productRepository;
        $this->entityManager      = $entityManager;
        $this->security           = $security;
        $this->customerorderrepo  = $customerorderrepo;
        $this->saleRepo           = $sale_repository;
        $this->orderItemrepo      = $orderItemrepo;
        $this->cancellationrepo   = $cancellationrepo;
        $this->companyService     = $companyService; // ← ADDED: assign it
    }

    // ─────────────────────────────────────────────────────────────────────────
    // processOrder — create a new order OR add items to an existing one
    // ─────────────────────────────────────────────────────────────────────────
    public function processOrder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        // Check if we are adding to an existing order or creating a new one
        if (isset($data['customerOrderId'])) {
            // Adding items to an existing order
            $customerOrder = $this->customerorderrepo->find($data['customerOrderId']);
            if (!$customerOrder) {
                throw new \Exception('Customer order not found');
            }
        } else {
            // Creating a brand new order
            $customerOrder = new CustomerOrder();
            $customerOrder->setWaiter($user);

            // ── ADDED: assign company to the order ────────────────────────
            // CustomerOrder.company_id is NOT NULL in the database
            // We must set it before calling persist()
            // getCurrentCompany() reads from the JWT token:
            //   - ROLE_ADMIN → finds company where owner = current user
            //   - other roles → reads user->getCompany()
            $company = $this->companyService->getCurrentCompany();
            if (!$company) {
                throw new \Exception('No company assigned to the authenticated user');
            }
            $customerOrder->setCompany($company);
            // ── END ADDED ─────────────────────────────────────────────────

            $this->entityManager->persist($customerOrder);
        }

        // Validate that items were provided
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \Exception("No items provided");
        }

        // Loop through each item and add it to the order
        foreach ($items as $item) {
            // Find the product
            $product = $this->productRepository->find($item['product_id']);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Check stock availability
            if ($item['quantity'] > $product->getQuantity()) {
                throw new \Exception('The quantity in stock is insufficient');
            }

            // Create the order item
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setPrice($product->getSaleprice());

            $this->entityManager->persist($orderItem);
            $customerOrder->addOrderItem($orderItem);

            // Decrease stock quantity
            $product->setQuantity($product->getQuantity() - $orderItem->getQuantity());
            $this->entityManager->persist($product);
        }

        // Save everything to the database
        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();

        // Calculate the total amount
        $totalAmount = 0;
        foreach ($customerOrder->getOrderItems() as $orderItem) {
            $totalAmount += $orderItem->getPrice() * $orderItem->getQuantity();
        }

        // Return the response data
        return [
            "message"      => isset($data['customerOrderId'])
                ? "Items added successfully"
                : "Order created successfully",
            "order_id"     => $customerOrder->getId(),
            "waiter_id"    => $customerOrder->getWaiter()->getId(),
            "total_amount" => $totalAmount,
            "items"        => $customerOrder->getOrderItems()
                ->map(function (OrderItem $orderItem) {
                    return [
                        "order_item_id" => $orderItem->getId(),
                        "product_id"    => $orderItem->getProduct()->getId(),
                        "product_name"  => $orderItem->getProduct()->getProductname(),
                        "quantity"      => $orderItem->getQuantity(),
                        "price"         => $orderItem->getPrice(),
                        "subtotal"      => $orderItem->getPrice() * $orderItem->getQuantity(),
                        "date_create"   => $orderItem->getCreatedAt()->format("Y-m-d H:i:s"),
                        "date_update"   => $orderItem->getUpdatedAt()->format("Y-m-d H:i:s"),
                    ];
                })->toArray()
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // createSaleFromOrder — validate an order and create a Sale record
    // ─────────────────────────────────────────────────────────────────────────
    public function createSaleFromOrder(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $customerOrder = $this->customerorderrepo->find($data['customerOrderId']);

        // Check if a sale already exists for this order (idempotency)
        $saleExists = $this->saleRepo->findOneBy(["customerOrder" => $customerOrder]);

        // Calculate total
        $totalAmount = 0;
        foreach ($customerOrder->getOrderItems() as $orderitem) {
            $totalAmount = $totalAmount + ($orderitem->getPrice() * $orderitem->getQuantity());
        }

        if ($saleExists) {
            // Return existing sale data (invoice copy)
            return [
                "sale_id"        => $saleExists->getId(),
                "teller"         => $saleExists->getTeller()->getName(),
                "order_id"       => $customerOrder->getId(),
                "total_amount"   => $totalAmount,
                "payment_method" => $saleExists->getPaymentMethod(),
                "items"          => $customerOrder->getOrderItems()
                    ->map(function (OrderItem $item) {
                        return [
                            "product_id"   => $item->getId(),
                            "product_name" => $item->getProduct()->getProductname(),
                            "quantity"     => $item->getQuantity(),
                            "price"        => $item->getPrice(),
                            "subtotal"     => $item->getPrice() * $item->getQuantity(),
                            "date_create"  => $item->getCreatedAt()->format("Y-m-d H:i:s"),
                            "date_update"  => $item->getUpdatedAt()->format("Y-m-d H:i:s"),
                        ];
                    })->toArray(),
                "invoice" => "copy"
            ];
        }

        // Create a new Sale
        $sale = new Sale();
        $user = $this->security->getUser();
        $sale->setTeller($user);
        $sale->setStatut(SaleStatut::PENDING->value);
        $sale->setCustomerOrder($customerOrder);
        $sale->setPaymentMethod($data['paymentMethod']);
        $sale->setIsPaid(false);

        $this->entityManager->persist($sale);
        $this->entityManager->flush();

        return [
            "sale_id"        => $sale->getId(),
            "statut"         => $sale->getStatut(),
            "teller"         => $sale->getTeller()->getName(),
            "order_id"       => $customerOrder->getId(),
            "total_amount"   => $totalAmount,
            "payment_method" => $sale->getPaymentMethod(),
            "items"          => $customerOrder->getOrderItems()
                ->map(function (OrderItem $item) {
                    return [
                        "product_id"   => $item->getId(),
                        "product_name" => $item->getProduct()->getProductname(),
                        "quantity"     => $item->getQuantity(),
                        "price"        => $item->getPrice(),
                        "subtotal"     => $item->getPrice() * $item->getQuantity(),
                        "date_create"  => $item->getCreatedAt()->format("Y-m-d H:i:s"),
                        "date_update"  => $item->getUpdatedAt()->format("Y-m-d H:i:s"),
                    ];
                })->toArray()
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // orderItemCancellation — cancel or update quantity of one order item
    // ─────────────────────────────────────────────────────────────────────────
    public function orderItemCancellation(Request $request)
    {
        $data     = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? null;

        if ($quantity !== null && $quantity <= 0) {
            throw new \Exception('Quantity must be greater than zero');
        }

        $user      = $this->security->getUser();
        $orderitem = $this->orderItemrepo->find($data['orderItemId']);

        if (!$orderitem) {
            throw new \Exception('The order item was not found.');
        }

        $product       = $orderitem->getProduct();
        $customerOrder = $orderitem->getCustomerOrder();

        if ($quantity !== null && $quantity > 0) {
            // Update the quantity of the order item
            $currentQuantity = $orderitem->getQuantity();

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
            $this->entityManager->persist($orderitem);
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return [[
                'itemid'           => $orderitem->getId(),
                'orderid'          => $customerOrder->getId(),
                'message'          => 'OrderItem quantity has been updated.',
                'current_quantity' => $currentQuantity,
                'items'            => $customerOrder->getOrderItems()
                    ->map(function (OrderItem $oi) {
                        return [
                            "product_id"          => $oi->getId(),
                            "product_name"        => $oi->getProduct()->getProductname(),
                            "price"               => $oi->getPrice(),
                            "new_quantity_item"   => $oi->getQuantity(),
                            "date_create"         => $oi->getCreatedAt()->format("Y-m-d H:i:s"),
                            "date_update"         => $oi->getUpdatedAt()->format("Y-m-d H:i:s"),
                        ];
                    })->toArray()
            ]];
        } else {
            // Remove the item entirely and restore stock
            $product->setQuantity($product->getQuantity() + $orderitem->getQuantity());
            $customerOrder->removeOrderItem($orderitem);
            $this->entityManager->remove($orderitem);
            $this->entityManager->persist($product);
        }

        $idcustomerorder = null;
        $waiterId        = null;

        // If the order is now empty, delete it too
        if ($customerOrder->getOrderItems()->isEmpty()) {
            $idcustomerorder = $customerOrder->getId();
            $waiterId        = $customerOrder->getWaiter()->getId();
            $this->entityManager->remove($customerOrder);
        }

        $this->entityManager->flush();

        return [
            "id_custormerOrder" => $idcustomerorder,
            "Waiter_id"         => $waiterId,
            "Items"             => $customerOrder->getOrderItems()
                ->map(function (OrderItem $oi) {
                    return [
                        "product_id"   => $oi->getId(),
                        "Product_name" => $oi->getProduct()->getProductname(),
                        "Quantity"     => $oi->getQuantity(),
                        "price"        => $oi->getPrice(),
                        "date_create"  => $oi->getCreatedAt()->format("Y-m-d H:i:s"),
                        "date_update"  => $oi->getUpdatedAt()->format("Y-m-d H:i:s"),
                    ];
                })->toArray()
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // cancelSale — cancel a sale and restore all stock quantities
    // ─────────────────────────────────────────────────────────────────────────
    public function cancelSale(Request $request)
    {
        $data   = json_decode($request->getContent(), true);
        $sale   = $this->saleRepo->find($data['saleId']);

        if (!$sale) {
            throw new \Exception("sale not found");
        }

        $reason = $data['reason'];

        // Prevent double cancellation
        if ($sale->getCancellation() !== null) {
            throw new \Exception('This sale has already been cancelled.');
        }

        $user          = $this->security->getUser();
        $customerOrder = $sale->getCustomerOrder();
        $orderItems    = $customerOrder->getOrderItems();

        // Restore stock for each item in the order
        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            $product->setQuantity($product->getQuantity() + $orderItem->getQuantity());
            $this->entityManager->persist($product);
        }

        // Mark the sale as cancelled
        $sale->setStatut(SaleStatut::CANCELLED->value);
        $this->entityManager->persist($sale);

        // Create the cancellation record
        $cancellation = new Cancellation();
        $cancellation->setUser($user);
        $cancellation->setSale($sale);
        $cancellation->setCancellationReason($reason);
        $this->entityManager->persist($cancellation);
        $this->entityManager->flush();

        return [
            "id_cancel"      => $cancellation->getId(),
            "cancelled_by"   => $cancellation->getUser(),
            "reason"         => $cancellation->getCancellationreason(),
            "sale_id"        => $sale->getId(),
            "teller"         => $sale->getTeller()->getName(),
            "order_id"       => $customerOrder->getId(),
            "total_amount"   => $sale->getTotalAmount(),
            "payment_method" => $sale->getPaymentMethod(),
            "items"          => $customerOrder->getOrderItems()
                ->map(function (OrderItem $item) {
                    return [
                        "product_id"   => $item->getId(),
                        "product_name" => $item->getProduct()->getProductname(),
                        "quantity"     => $item->getQuantity(),
                        "price"        => $item->getPrice(),
                        "subtotal"     => $item->getPrice() * $item->getQuantity(),
                        "date_create"  => $item->getCreatedAt()->format("Y-m-d H:i:s"),
                        "date_update"  => $item->getUpdatedAt()->format("Y-m-d H:i:s"),
                    ];
                })->toArray()
        ];
    }
}