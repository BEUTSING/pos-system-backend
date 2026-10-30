<?php

namespace App\Service\Billing;

use App\Entity\Billing\Invoice;
use App\Entity\Billing\InvoiceItem;
use App\Entity\Checkout\Customer;
use App\Entity\Checkout\Sale;
use App\Entity\Company\Company;
use App\Entity\Product\Product;
use App\Repository\Billing\InvoiceRepository;
use App\Repository\Checkout\CustomerRepository;
use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use App\Service\Company\CompanyService;
use App\Service\Media\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Billing module: invoices are generated from sales or created manually,
 * always scoped to the active company.
 */
class InvoiceService
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private SaleRepository $saleRepository,
        private CustomerRepository $customerRepository,
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private CompanyService $companyService,
        private FileUploadService $fileUploadService,
    ) {
    }

    public function listInvoices(): array
    {
        $company = $this->getCurrentCompany();

        return array_map(
            fn (Invoice $invoice): array => $this->formatInvoice($invoice),
            $this->invoiceRepository->findForCompany($company)
        );
    }

    public function getInvoice(int $id): array
    {
        return $this->formatInvoice($this->findInvoice($id));
    }

    public function createInvoice(Request $request): array
    {
        $company = $this->getCurrentCompany();
        $data = $this->decode($request);

        if (!empty($data['sale_id'])) {
            return $this->createFromSale((int) $data['sale_id'], isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0.0);
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new \InvalidArgumentException('At least one invoice line is required');
        }

        $invoice = new Invoice();
        $invoice->setCompany($company);
        $invoice->setReference($this->nextReference($company));
        $invoice->setStatus($this->validStatus($data['status'] ?? Invoice::STATUS_DRAFT));
        $invoice->setTaxRate($this->normalizeTaxRate($data['tax_rate'] ?? 0));
        $invoice->setNotes($this->nullableString($data['notes'] ?? null));
        $invoice->setDueAt($this->parseDate($data['due_date'] ?? null));

        if (!empty($data['customer_id'])) {
            $customer = $this->findCustomer((int) $data['customer_id'], $company);
            $invoice->setCustomer($customer);
        }

        $subtotal = 0.0;
        foreach ($items as $line) {
            if (!is_array($line)) {
                throw new \InvalidArgumentException('Each invoice line must be an object');
            }
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Invoice line quantity must be greater than zero');
            }
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            if ($unitPrice < 0) {
                throw new \InvalidArgumentException('Invoice line price cannot be negative');
            }

            $product = null;
            if (!empty($line['product_id'])) {
                $product = $this->findProduct((int) $line['product_id'], $company);
            }
            $label = trim((string) ($line['label'] ?? $product?->getProductname() ?? ''));
            if ($label === '') {
                throw new \InvalidArgumentException('Invoice line label is required');
            }

            $item = new InvoiceItem();
            $item->setProduct($product);
            $item->setLabel($label);
            $item->setQuantity($quantity);
            $item->setUnitPrice(number_format($unitPrice, 2, '.', ''));
            $item->setLineTotal(number_format($unitPrice * $quantity, 2, '.', ''));
            $invoice->addItem($item);
            $subtotal += $unitPrice * $quantity;
        }

        $this->applyTotals($invoice, $subtotal);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $this->formatInvoice($invoice);
    }

    public function createFromSale(int $saleId, float $taxRate = 0.0): array
    {
        $company = $this->getCurrentCompany();
        $sale = $this->saleRepository->find($saleId);
        if (!$sale || $sale->getCompany()?->getId() !== $company->getId()) {
            throw new \RuntimeException('Sale not found in the active company');
        }

        $existing = $this->invoiceRepository->findOneForSale($sale, $company);
        if ($existing) {
            return $this->formatInvoice($existing);
        }

        $order = $sale->getCustomerOrder();
        if (!$order) {
            throw new \RuntimeException('This sale has no order to invoice');
        }

        $invoice = new Invoice();
        $invoice->setCompany($company);
        $invoice->setSale($sale);
        $invoice->setCustomer($order->getCustomer());
        $invoice->setReference($this->nextReference($company));
        $invoice->setStatus(Invoice::STATUS_DRAFT);
        $invoice->setTaxRate($this->normalizeTaxRate($taxRate));

        $subtotal = 0.0;
        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $quantity = (int) $orderItem->getQuantity();
            $unitPrice = (float) $orderItem->getPrice();

            $item = new InvoiceItem();
            $item->setProduct($product);
            $item->setLabel($product?->getProductname() ?? 'Article');
            $item->setQuantity($quantity);
            $item->setUnitPrice(number_format($unitPrice, 2, '.', ''));
            $item->setLineTotal(number_format($unitPrice * $quantity, 2, '.', ''));
            $invoice->addItem($item);
            $subtotal += $unitPrice * $quantity;
        }

        $this->applyTotals($invoice, $subtotal);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $this->formatInvoice($invoice);
    }

    public function updateInvoice(int $id, Request $request): array
    {
        $invoice = $this->findInvoice($id);
        $data = $this->decode($request);

        if (array_key_exists('status', $data)) {
            $invoice->setStatus($this->validStatus($data['status']));
        }
        if (array_key_exists('notes', $data)) {
            $invoice->setNotes($this->nullableString($data['notes']));
        }
        if (array_key_exists('due_date', $data)) {
            $invoice->setDueAt($this->parseDate($data['due_date']));
        }
        if (array_key_exists('tax_rate', $data)) {
            $invoice->setTaxRate($this->normalizeTaxRate($data['tax_rate']));
            $subtotal = 0.0;
            foreach ($invoice->getItems() as $item) {
                $subtotal += (float) $item->getLineTotal();
            }
            $this->applyTotals($invoice, $subtotal);
        }

        $this->entityManager->flush();

        return $this->formatInvoice($invoice);
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = $this->findInvoice($id);
        $this->entityManager->remove($invoice);
        $this->entityManager->flush();
    }

    private function findInvoice(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findOneForCompany($id, $this->getCurrentCompany());
        if (!$invoice) {
            throw new \RuntimeException('Invoice not found');
        }

        return $invoice;
    }

    private function getCurrentCompany(): Company
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        return $company;
    }

    private function findCustomer(int $id, Company $company): Customer
    {
        $customer = $this->customerRepository->find($id);
        if (!$customer || $customer->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Customer not found in the active company');
        }

        return $customer;
    }

    private function findProduct(int $id, Company $company): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product || $product->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Product not found in the active company');
        }

        return $product;
    }

    private function applyTotals(Invoice $invoice, float $subtotal): void
    {
        $taxRate = (float) $invoice->getTaxRate();
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $invoice->setSubtotal(number_format($subtotal, 2, '.', ''));
        $invoice->setTaxAmount(number_format($taxAmount, 2, '.', ''));
        $invoice->setTotal(number_format($subtotal + $taxAmount, 2, '.', ''));
    }

    private function nextReference(Company $company): string
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $sequence = $this->invoiceRepository->countForCompanyAndYear($company, $year) + 1;

        do {
            $reference = sprintf('FAC-%d-%04d', $year, $sequence);
            $exists = $this->invoiceRepository->findOneBy(['reference' => $reference]);
            $sequence++;
        } while ($exists);

        return $reference;
    }

    private function normalizeTaxRate(mixed $value): string
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('The tax rate must be numeric');
        }
        $rate = (float) $value;
        if ($rate < 0 || $rate > 100) {
            throw new \InvalidArgumentException('The tax rate must be between 0 and 100');
        }

        return number_format($rate, 2, '.', '');
    }

    private function validStatus(mixed $status): string
    {
        $status = is_string($status) ? $status : Invoice::STATUS_DRAFT;
        if (!in_array($status, Invoice::STATUSES, true)) {
            throw new \InvalidArgumentException('Unsupported invoice status');
        }

        return $status;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('The date must be a string');
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid date format');
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('The value must be a string');
        }

        return trim($value);
    }

    /** @return array<string, mixed> */
    private function decode(Request $request): array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Request body must contain valid JSON', 0, $exception);
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Request body must be a JSON object');
        }

        return $data;
    }

    private function formatInvoice(Invoice $invoice): array
    {
        $company = $invoice->getCompany();
        $customer = $invoice->getCustomer();
        $sale = $invoice->getSale();
        $lines = [];

        foreach ($invoice->getItems() as $item) {
            $product = $item->getProduct();
            $lines[] = [
                'id' => $item->getId(),
                'productId' => $product?->getId(),
                'product_id' => $product?->getId(),
                'reference' => $product?->getReference(),
                'name' => $item->getLabel(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => (float) $item->getUnitPrice(),
                'price' => (float) $item->getUnitPrice(),
                'lineTotal' => (float) $item->getLineTotal(),
                'subtotal' => (float) $item->getLineTotal(),
            ];
        }

        $subtotal = (float) $invoice->getSubtotal();
        $taxAmount = (float) $invoice->getTaxAmount();
        $totalTTC = (float) $invoice->getTotal();

        return [
            'id' => $invoice->getId(),
            'reference' => $invoice->getReference(),
            'sale_id' => $sale?->getId(),
            'saleId' => $sale?->getId(),
            'order_id' => $sale?->getCustomerOrder()?->getId(),
            'orderId' => $sale?->getCustomerOrder()?->getId(),
            'customer_id' => $customer?->getId(),
            'clientId' => $customer?->getId(),
            'customer' => $customer ? [
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'city' => $customer->getCity(),
                'address' => $customer->getAddress(),
                'picture' => $this->fileUploadService->publicUrl($customer->getPicture()),
            ] : null,
            'clientName' => $customer?->getName() ?? 'Client comptoir',
            'customer_name' => $customer?->getName(),
            'lines' => $lines,
            'items' => $lines,
            'subtotal' => $subtotal,
            'taxRate' => (float) $invoice->getTaxRate(),
            'tax_rate' => (float) $invoice->getTaxRate(),
            'taxAmount' => $taxAmount,
            'tax_amount' => $taxAmount,
            'totalTTC' => $totalTTC,
            // `total` is kept as the HT amount for existing consumers.
            'total' => $subtotal,
            'total_amount' => $totalTTC,
            'status' => $invoice->getStatus(),
            'notes' => $invoice->getNotes(),
            'createdAt' => $invoice->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'issuedAt' => $invoice->getIssuedAt()?->format(\DateTimeInterface::ATOM),
            'dueAt' => $invoice->getDueAt()?->format(\DateTimeInterface::ATOM),
            'company' => $company ? [
                'id' => $company->getId(),
                'name' => $company->getNameComp(),
                'email' => $company->getEmailComp(),
                'phone' => $company->getPhone(),
                'city' => $company->getCity(),
                'address' => $company->getAddress(),
                'country' => $company->getCountry(),
                'currency' => $company->getCurrency(),
                'logo' => $this->fileUploadService->publicUrl($company->getLogo()),
            ] : null,
        ];
    }
}
