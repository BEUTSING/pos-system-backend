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
use App\Repository\Checkout\CustomerRepository;
use App\Repository\Company\CompanyRepository;
use App\Repository\Product\ProductRepository;
use App\Service\Company\CompanyService;

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
    private $companyService;
    private $companyRepository;
    private $customerRepository;

    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        CustomerOrderRepository $customerorderrepo,
        CancellationRepository $cancellationrepo,
        SaleRepository $sale_repository,
        OrderItemRepository $orderItemrepo,
        CompanyService $companyService,
        CompanyRepository $companyRepository,
        CustomerRepository $customerRepository
    ) {
        $this->productRepository  = $productRepository;
        $this->entityManager      = $entityManager;
        $this->security           = $security;
        $this->customerorderrepo  = $customerorderrepo;
        $this->saleRepo           = $sale_repository;
        $this->orderItemrepo      = $orderItemrepo;
        $this->cancellationrepo   = $cancellationrepo;
        $this->companyService     = $companyService;
        $this->companyRepository  = $companyRepository;
        $this->customerRepository = $customerRepository;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // processOrder — create a new order OR add items to an existing one
    // ─────────────────────────────────────────────────────────────────────────
    public function processOrder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items']) || $data['items'] === []) {
            throw new \InvalidArgumentException('At least one order item is required');
        }

        // Check if we are adding to an existing order or creating a new one
        if (isset($data['customerOrderId'])) {
            // Adding items to an existing order
            $customerOrder = $this->customerorderrepo->find($data['customerOrderId']);
            if (!$customerOrder || $customerOrder->getCompany()?->getId() !== $company->getId()) {
                throw new \RuntimeException('Customer order not found');
            }
            if ($customerOrder->getSale() !== null) {
                throw new \InvalidArgumentException('Items cannot be added to a sold order');
            }
            $customerId = $data['customer_id'] ?? $data['customerId'] ?? null;
            if ($customerId !== null) {
                $customer = $this->customerRepository->findOneBy(['id' => (int) $customerId, 'company' => $company]);
                if (!$customer) {
                    throw new \InvalidArgumentException('Customer not found in the active company');
                }
                $customerOrder->setCustomer($customer);
            }
        } else {
            // Creating a brand new order
            $customerOrder = new CustomerOrder();
            $customerOrder->setWaiter($user);

            $customerOrder->setCompany($company);
            $customerId = $data['customer_id'] ?? $data['customerId'] ?? null;
            if ($customerId !== null) {
                $customer = $this->customerRepository->findOneBy(['id' => (int) $customerId, 'company' => $company]);
                if (!$customer) {
                    throw new \InvalidArgumentException('Customer not found in the active company');
                }
                $customerOrder->setCustomer($customer);
            }

            $this->entityManager->persist($customerOrder);
        }

        // Draft orders do not reserve stock, but all requested quantities must fit together.
        $requestedQuantities = [];
        // Add each product after checking company ownership and available stock.
        foreach ($data['items'] as $item) {
            if (!is_array($item) || !isset($item['product_id'], $item['quantity']) || !is_numeric($item['quantity']) || (int) $item['quantity'] < 1) {
                throw new \InvalidArgumentException('Each item requires a product_id and a positive quantity');
            }
            $product = $this->productRepository->find((int) $item['product_id']);
            if (!$product || $product->getCompany()?->getId() !== $company->getId()) {
                throw new \RuntimeException('Product not found in the active company');
            }
            $productId = (int) $product->getId();
            $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + (int) $item['quantity'];
            if ($requestedQuantities[$productId] > $product->getQuantity()) {
                throw new \InvalidArgumentException('The quantity in stock is insufficient');
            }

            // Create the order item
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setPrice($product->getSaleprice());
            $orderItem            ->setCompany($company);

            $this->entityManager->persist($orderItem);
            $customerOrder->addOrderItem($orderItem);

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
            "id"           => $customerOrder->getId(),
            "order_id"     => $customerOrder->getId(),
            "waiter_id"    => $customerOrder->getWaiter()->getId(),
            "customer_id"  => $customerOrder->getCustomer()?->getId(),
            "customer_name" => $customerOrder->getCustomer()?->getName(),
            "created_at"   => $customerOrder->getCreatedAt()->format('Y-m-d H:i:s'),
            "total_amount" => $totalAmount,
            "items"        => $customerOrder->getOrderItems()
                ->map(function (OrderItem $orderItem) {
                    return [
                        "order_item_id" => $orderItem->getId(),
                        "product_id"    => $orderItem->getProduct()->getId(),
                        "product_name"  => $orderItem->getProduct()->getProductname(),
                        "reference"     => $orderItem->getProduct()->getReference(),
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

        $company = $this->companyService->getCurrentCompany();
        $customerOrder = $this->customerorderrepo->find((int) ($data['customerOrderId'] ?? 0));
        if (!$company || !$customerOrder || $customerOrder->getCompany()?->getId() !== $company->getId()) {
            throw new \RuntimeException('Customer order not found in the active company');
        }

        // Check if a sale already exists for this order (idempotency)
        $saleExists = $this->saleRepo->findOneBy(["customerOrder" => $customerOrder]);

        // Calculate total
        $totalAmount = 0;
        foreach ($customerOrder->getOrderItems() as $orderitem) {
            $totalAmount = $totalAmount + ($orderitem->getPrice() * $orderitem->getQuantity());
        }

        if ($saleExists) {
            if ($saleExists->getStatut() === SaleStatut::CANCELLED->value) {
                throw new \InvalidArgumentException('A cancelled sale cannot be reconfirmed');
            }
            // Return existing sale data (invoice copy)
            return [
                "sale_id"        => $saleExists->getId(),
                "teller"         => $saleExists->getTeller()->getName(),
            "order_id"       => $customerOrder->getId(),
            "customer_id"    => $customerOrder->getCustomer()?->getId(),
            "customer_name"  => $customerOrder->getCustomer()?->getName(),
            "date_created"   => $saleExists->getCreatedAt()->format('Y-m-d H:i:s'),
            "statut"         => $saleExists->getStatut(),
            "total_amount"   => $totalAmount,
            "payment_method" => $saleExists->getPaymentMethod(),
                "items"          => $customerOrder->getOrderItems()
                    ->map(function (OrderItem $item) {
                        return [
                            "order_item_id" => $item->getId(),
                            "product_id"   => $item->getProduct()->getId(),
                            "product_name" => $item->getProduct()->getProductname(),
                            "reference"    => $item->getProduct()->getReference(),
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

        // Validate and deduct stock only when the draft order becomes a sale.
        foreach ($customerOrder->getOrderItems() as $orderItem) {
            if ($orderItem->getQuantity() > $orderItem->getProduct()->getQuantity()) {
                throw new \InvalidArgumentException(sprintf('Insufficient stock for %s', $orderItem->getProduct()->getProductname()));
            }
        }
        foreach ($customerOrder->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $product->setQuantity($product->getQuantity() - $orderItem->getQuantity());
        }

        // Create a new Sale
        $sale = new Sale();
        $user = $this->security->getUser();
        $sale->setTeller($user);
        $sale->setStatut(SaleStatut::PENDING->value);
        $sale->setCustomerOrder($customerOrder);
        $sale->setPaymentMethod((string) ($data['paymentMethod'] ?? 'Cash'));
        $sale->setIsPaid(false);
        // Assign company from the order (inherited from active company)
        $sale->setCompany($customerOrder->getCompany());

        $this->entityManager->persist($sale);
        $this->entityManager->flush();

        return [
            "sale_id"        => $sale->getId(),
            "statut"         => $sale->getStatut(),
            "teller"         => $sale->getTeller()->getName(),
            "order_id"       => $customerOrder->getId(),
            "customer_id"    => $customerOrder->getCustomer()?->getId(),
            "customer_name"  => $customerOrder->getCustomer()?->getName(),
            "date_created"   => $sale->getCreatedAt()->format('Y-m-d H:i:s'),
            "total_amount"   => $totalAmount,
            "payment_method" => $sale->getPaymentMethod(),
            "items"          => $customerOrder->getOrderItems()
                ->map(function (OrderItem $item) {
                    return [
                        "order_item_id" => $item->getId(),
                        "product_id"   => $item->getProduct()->getId(),
                        "product_name" => $item->getProduct()->getProductname(),
                        "reference"    => $item->getProduct()->getReference(),
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

        $company = $this->companyService->getCurrentCompany();
        $orderitem = $this->orderItemrepo->find((int) ($data['orderItemId'] ?? 0));

        if (!$company || !$orderitem || $orderitem->getCompany()?->getId() !== $company->getId()) {
            throw new \RuntimeException('The order item was not found in the active company.');
        }

        $product = $orderitem->getProduct();
        $customerOrder = $orderitem->getCustomerOrder();
        if ($customerOrder->getSale() !== null) {
            throw new \InvalidArgumentException('Items from a sold order cannot be modified; cancel the sale instead.');
        }

        if ($quantity !== null && $quantity > 0) {
            // Draft orders do not reserve stock; check only that the revised order fits inventory.
            $currentQuantity = $orderitem->getQuantity();
            $quantityForProduct = $customerOrder->getOrderItems()
                ->filter(static fn (OrderItem $item): bool => $item->getProduct()->getId() === $product->getId())
                ->map(static fn (OrderItem $item): int => $item->getQuantity())
                ->reduce(static fn (int $total, int $itemQuantity): int => $total + $itemQuantity, 0);
            if ($quantityForProduct - $currentQuantity + (int) $quantity > $product->getQuantity()) {
                throw new \InvalidArgumentException('The quantity in stock is insufficient');
            }

            $orderitem->setQuantity($quantity);
            $this->entityManager->persist($orderitem);
            $this->entityManager->flush();

            return [[
                'itemid'           => $orderitem->getId(),
                'orderid'          => $customerOrder->getId(),
                'message'          => 'OrderItem quantity has been updated.',
                'current_quantity' => $currentQuantity,
                'items'            => $customerOrder->getOrderItems()
                    ->map(function (OrderItem $oi) {
                        return [
                            "order_item_id"       => $oi->getId(),
                            "product_id"          => $oi->getProduct()->getId(),
                            "product_name"        => $oi->getProduct()->getProductname(),
                            "price"               => $oi->getPrice(),
                            "new_quantity_item"   => $oi->getQuantity(),
                            "date_create"         => $oi->getCreatedAt()->format("Y-m-d H:i:s"),
                            "date_update"         => $oi->getUpdatedAt()->format("Y-m-d H:i:s"),
                        ];
                    })->toArray()
            ]];
        } else {
            // Draft orders do not reserve stock; removing an item changes no inventory.
            $customerOrder->removeOrderItem($orderitem);
            $this->entityManager->remove($orderitem);
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
                        "order_item_id" => $oi->getId(),
                        "product_id"   => $oi->getProduct()->getId(),
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
        $data = json_decode($request->getContent(), true);
        $company = $this->companyService->getCurrentCompany();
        $sale = $this->saleRepo->find((int) ($data['saleId'] ?? 0));

        if (!$company || !$sale || $sale->getCompany()?->getId() !== $company->getId()) {
            throw new \RuntimeException('Sale not found in the active company');
        }

        $reason = (string) ($data['reason'] ?? 'Annulation depuis le module commandes');

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
            "total_amount"   => array_sum(array_map(
                static fn (OrderItem $item): float => $item->getPrice() * $item->getQuantity(),
                $orderItems->toArray(),
            )),
            "payment_method" => $sale->getPaymentMethod(),
            "items"          => $customerOrder->getOrderItems()
                ->map(function (OrderItem $item) {
                    return [
                        "order_item_id" => $item->getId(),
                        "product_id"   => $item->getProduct()->getId(),
                        "product_name" => $item->getProduct()->getProductname(),
                        "reference"    => $item->getProduct()->getReference(),
                        "quantity"     => $item->getQuantity(),
                        "price"        => $item->getPrice(),
                        "subtotal"     => $item->getPrice() * $item->getQuantity(),
                        "date_create"  => $item->getCreatedAt()->format("Y-m-d H:i:s"),
                        "date_update"  => $item->getUpdatedAt()->format("Y-m-d H:i:s"),
                    ];
                })->toArray()
        ];
    }

    public function listOrders(): array
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        $orders = $this->customerorderrepo->createQueryBuilder('o')
            ->leftJoin('o.sale', 's')->addSelect('s')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.orderItems', 'i')->addSelect('i')
            ->leftJoin('i.product', 'p')->addSelect('p')
            ->where('o.company = :company')->setParameter('company', $company)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()->getResult();

        return array_map(function (CustomerOrder $order): array {
            $items = [];
            $total = 0;
            foreach ($order->getOrderItems() as $item) {
                $subtotal = $item->getPrice() * $item->getQuantity();
                $total += $subtotal;
                $items[] = [
                    'order_item_id' => $item->getId(),
                    'product_id' => $item->getProduct()->getId(),
                    'product_name' => $item->getProduct()->getProductname(),
                    'reference' => $item->getProduct()->getReference(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                    'subtotal' => $subtotal,
                ];
            }
            $sale = $order->getSale();
            return [
                'id' => $order->getId(),
                'order_id' => $order->getId(),
                'customer_id' => $order->getCustomer()?->getId(),
                'customer_name' => $order->getCustomer()?->getName(),
                'status' => $sale?->getStatut() === SaleStatut::CANCELLED->value ? 'cancelled' : ($sale ? 'confirmed' : 'draft'),
                'sale_id' => $sale?->getId(),
                'invoiced' => $sale !== null && $sale->getStatut() !== SaleStatut::CANCELLED->value,
                'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'total_amount' => $total,
                'items' => $items,
            ];
        }, $orders);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getPendingOrders — get orders that don't have a sale yet
    // ─────────────────────────────────────────────────────────────────────────
    public function deleteDraftOrder(int $id): void
    {
        $company = $this->companyService->getCurrentCompany();
        $order = $this->customerorderrepo->find($id);
        if (!$company || !$order || $order->getCompany()?->getId() !== $company->getId()) {
            throw new \RuntimeException('Order not found in the active company');
        }
        if ($order->getSale() !== null) {
            throw new \InvalidArgumentException('A sold order cannot be deleted; cancel the sale instead');
        }
        foreach ($order->getOrderItems() as $item) {
            $this->entityManager->remove($item);
        }
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }

    public function getPendingOrders(): array
    {
        $company = $this->companyService->getCurrentCompany();
        
        // For admin users: if no active company from header, fallback to first owned company
        if (!$company) {
            $user = $this->security->getUser();
            if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
                $company = $this->companyRepository->findOneBy(['owner' => $user]);
            }
        }
        
        if (!$company) {
            throw new \Exception('No company assigned to the authenticated user');
        }

        // Get all orders for this company that don't have a sale
        $orders = $this->customerorderrepo->createQueryBuilder('o')
            ->leftJoin('o.sale', 's')
            ->where('o.company = :company')
            ->andWhere('s.id IS NULL')
            ->setParameter('company', $company)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id'          => $order->getId(),
                'waiter'      => $order->getWaiter()?->getName() ?? 'Serveur',
                'created_at'  => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'total_amount' => $order->getOrderItems()->map(function (OrderItem $item) {
                    return $item->getPrice() * $item->getQuantity();
                })->reduce(function ($carry, $item) {
                    return $carry + $item;
                }, 0),
                'items_count' => $order->getOrderItems()->count(),
            ];
        }

        return $data;
    }
}