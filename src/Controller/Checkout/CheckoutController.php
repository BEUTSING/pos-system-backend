<?php

namespace App\Controller\Checkout;

use App\Service\Checkout\CheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout')]  
final class CheckoutController extends AbstractController
{

    private $checkoutService;
    
    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService=$checkoutService;
        
    }

  #[Route('/order', name:'app_checkout_order',methods:['POST'] )]
    public function Order(Request $request): JsonResponse{
      $data= $this->checkoutService->processOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

  #[Route("/add", name:"app_checkout_addItem", methods: ["POST"])]
  
  public function addOrderItemToOrder(Request $request): JsonResponse{

    $data= $this->checkoutService->addOrderItemToOrder($request);

    return new JsonResponse([
        "Success" => "true",
        "message" => $data
    ]);
  }

  #[Route('/sale', name:'app_checkout_sale',methods:['POST'] )]
    public function sale(Request $request): JsonResponse{
      $data= $this->checkoutService->createSaleFromOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

#[Route('/cancel', name:'cancel',methods:['POST'] )]
    public function cancel(Request $request): JsonResponse{
      $data= $this->checkoutService->orderItemCancellation($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

  #[Route('/cancelsale', name:'slcancel',methods:['POST'] )]
    public function cancelSale(Request $request): JsonResponse{
      $data= $this->checkoutService->cancelSale($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }
    
} 
