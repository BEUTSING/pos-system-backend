<?php

namespace App\Service\Statistic;

use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use App\Service\Company\CompanyService;
use Symfony\Component\HttpFoundation\Request;

class StatisticService
{
    private $salerepository;
    private $productRepository;
    private $companyService;

    public function __construct(
        SaleRepository $salerepository,
        ProductRepository $productRepository,
        CompanyService $companyService
    ) {
        $this->salerepository = $salerepository;
        $this->productRepository = $productRepository;
        $this->companyService = $companyService;
    }

    // ─── GET SALES BY PERIOD: filter sales by a given time period ────────────────
    public function getSaleStatistic(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        $period = $data['period'];

        $startdate = new \DateTimeImmutable();
        $enddate   = new \DateTimeImmutable();

        switch ($period) {
            case 'today':
                $startdate = $startdate->setTime(0, 0, 0);
                break;
            case 'yesterday':
                $startdate = $startdate->modify('-1 day')->setTime(0, 0, 0);
                $enddate   = $enddate->modify('-1 day')->setTime(23, 59, 59);
                break;
            case 'this_week':
                $startdate = $startdate->modify('monday this week')->setTime(0, 0, 0);
                break;
            case 'this_month':
                $startdate = $startdate->modify('first day of this month')->setTime(0, 0, 0);
                break;
            case 'this_year': // ← fixed: was "this_year"; (semicolon instead of colon)
                $startdate = $startdate->modify('first day of january this year')->setTime(0, 0, 0);
                break;
            default:
                return [];
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        // Filter sales by period AND current company
        $sales = $this->salerepository->findSalesByPeriodAndCompany($startdate, $enddate, $company);

        return $this->formatSalesData($sales);
    }

    // ─── GET ALL SALES: retrieve all sales of the current company ────────────────
    public function getAllSales(): array
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        // Filter sales by the current company only — not findAll()
        $sales = $this->salerepository->findBy(['company' => $company]);

        return $this->formatSalesData($sales);
    }

    // ─── GET SALES BY SELLER: filter sales by a specific teller ─────────────────
    public function getSalesBySeller(Request $request): array
    {
        $data   = json_decode($request->getContent(), true);
        $teller = $data['teller_id'];

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        // Filter by teller AND current company
        $sales = $this->salerepository->findBy(['teller' => $teller, 'company' => $company]);
        if (!$sales) {
            throw new \Exception('Sales not found for this seller');
        }

        return $this->formatSalesData($sales);
    }

    // ─── GET SALES BY CATEGORY: filter sales by a specific product category ──────
    public function getSalesByCategory(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['categoryId'])) {
            throw new \InvalidArgumentException('The field categoryId is required');
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        $sales = $this->salerepository->findSaleByCategoryAndCompany($data['categoryId'], $company);
        if (!$sales) {
            throw new \Exception('Sales not found for this category');
        }

        return $this->formatSalesData($sales);
    }

    // ─── GET SALES BY PRODUCT: filter sales by a specific product ────────────────
    public function getSalesByProduct(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        $product = $this->productRepository->find($data['productId']);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new \RuntimeException('No company assigned to the authenticated user');
        }

        $sales = $this->salerepository->findSaleByProductAndCompany($product, $company);

        return $this->formatSalesData($sales);
    }

    // ─── HELPER: format sales data into a structured array ───────────────────────
    private function formatSalesData(array $sales): array
    {
        $data          = [];
        $totalsale     = 0;
        $totalpurchase = 0;
        $profit        = 0; // ← fixed: initialized to 0 to avoid undefined variable crash

        foreach ($sales as $sale) {
            $productsInSale = [];

            // Check if the sale has a customer order
            if ($sale->getCustomerOrder()) {
                foreach ($sale->getCustomerOrder()->getOrderItems() as $orderItem) {
                    $saleprice     = $orderItem->getPrice();
                    $purchaseprice = $orderItem->getProduct()->getPurchasePrice();
                    $quantity      = $orderItem->getQuantity();

                    // Accumulate totals
                    $totalsale     += $saleprice * $quantity;
                    $totalpurchase += $purchaseprice * $quantity;

                    $productsInSale[] = [
                        'product_name' => $orderItem->getProduct()->getProductname(),
                        'quantity'     => $quantity,
                        'price'        => $saleprice,
                    ];
                }

                // Recalculate profit after each sale
                $profit = $totalsale - $totalpurchase;
            }

            $data[] = [
                'id'           => $sale->getId(),
                'teller'       => $sale->getTeller()->getName(),
                'products'     => $productsInSale,
                'date_created' => $sale->getCreatedAt()->format('Y-m-d H:i:s'),
                'date_update'  => $sale->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'total_sales'     => $totalsale,
            'total_purchases' => $totalpurchase,
            'profit'          => $profit,
            'sales'           => $data,
        ];
    }
}