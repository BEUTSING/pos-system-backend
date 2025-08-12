<?php

namespace App\Controller\Statistic;

use App\Service\Statistic\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sale_history')]
final class StatisticController extends AbstractController
{
    private $statistics;
    public function __construct(StatisticService $statistics) 
    {
        $this->statistics = $statistics;
    }
    

    #[Route('/period', name: 'app_statistic_period', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function getSaleStatistic( Request $request):JsonResponse{
        $data=$this->statistics->getSaleStatistic( $request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/all', name:'app_statistic_all', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function getAllSales():JsonResponse{

        $data=$this->statistics->getAllSales();    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }
    
    #[Route('/teller', name: 'app_sales_by_seller', methods: ['GET'])]
        #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function getSalesBySeller( Request $request):JsonResponse{
    
    $data=$this->statistics->getSalesBySeller($request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/categoryToSale', name: 'app_sales_by_category', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
     public function getSalesByCategory(Request $request):JsonResponse{
        $data=$this->statistics->getSalesByCategory($request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/product', name: 'app_sales_by_product', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

     public function getSalesByProduct(Request $request):JsonResponse{
        $data=$this->statistics->getSalesByProduct( $request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }
}
