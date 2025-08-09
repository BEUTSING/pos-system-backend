<?php

namespace App\Service\Statistic;

use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use Symfony\Component\HttpFoundation\Request;

class StatisticService

{
    private $salerepository;
    private $productRepository;
    public function __construct(SaleRepository $salerepository,ProductRepository $productRepository){
        $this->salerepository = $salerepository;
        $this->productRepository = $productRepository;
    }

    public function getSaleStatistic( Request $request){

        $data=json_decode($request->getContent(),true);

        $period= $data["period"];
        $startdate= new \DateTimeImmutable();
        $enddate= new \DateTimeImmutable();

        switch ($period) {
            case "today":
                $startdate=$startdate->setTime(0,0,0);
                break;
            case "yesterday":
                $startdate=$startdate->modify("-1 day")->setTime(0,0,0);  
                $enddate=$enddate->modify("-1 day")->setTime(23,59,59);
                break;
            case "this_week":
                $startdate=$startdate->modify("monday this week")->setTime(0,0,0);
                break;
            case "this_month":
                $startdate=$startdate->modify("first day of this month")->setTime(0,0,0);
                break;
            case "this_year";
                $startdate=$startdate->modify("first day of january this year")->setTime(0,0,0);
                break;
            default:
            // Handle custom period if needed
            return [];
        }
        $sale=$this->salerepository->findSalesByPeriod($startdate,$enddate);
        return $this->formatSalesData($sale);
}

//get a list of sales
public function getAllSales(){
    dd("");
     $sales = $this->salerepository->findAll();
     dd(vars: $sales);
        return $this->formatSalesData($sales);
} 

public function getSalesBySeller(Request $request)
    {
        $data=json_decode($request->getContent(),true);
        $teller=$data["teller_id"];
        $sales = $this->salerepository->findBy(['teller' => $teller]);

        if(!$sales){
            throw new \Exception("Sales not found for this seller");}
            
        return $this->formatSalesData($sales);
    }

public function getSalesByCategory(Request $request)
    {
        $data=json_decode($request->getContent(),true);
           if (!isset($data['categoryId'])) {
        throw new \Exception('thi');
    }
    
        $categoryId=$data["categoryId"];
        $sales = $this->salerepository->findSaleByCategory($categoryId);
        if(!$sales){
            throw new \Exception("sales not found for this category");}
            
        return $this->formatSalesData($sales);
    }    
public function getSalesByProduct(Request $request)
    {
        $data=json_decode($request->getContent(),true);

        $product = $this->productRepository->find($data["productId"]);
        if (!$product) {
            return [];
        }
        $sales = $this->salerepository->findSalebyproduct($product);
        return $this->formatSalesData($sales);
}

private function formatSalesData(array $sales){

    $data = [];
    foreach($sales as $sale){

        $productsInSale = [];
        // Check if the sale has a customer order
        if ($sale->getCustomerOrder()) {
            foreach ($sale->getCustomerOrder()->getOrderItems() as $orderItem) {
                $productsInSale[] = [
                    'product_name' => $orderItem->getProduct()->getProductname(),
                    'quantity' => $orderItem->getQuantity(),
                    'price' => $orderItem->getPrice(),
                ];
            }
        }

        $data[] = [
            'id'=> $sale->getId(),
            'teller'=>$sale->getTeller()->getName(),
            'products'=> $productsInSale,
            'total_amount'=>$sale->getTotalAmount(),
            'date_created'=>$sale->getCreatedAt()->format('Y-m-d H:i:s'),
            'date_update'=>$sale->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];    
}
return $data;
}
}