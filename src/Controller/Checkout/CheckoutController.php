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

  #[Route('', name:'app_checkout',methods:['POST'] )]
    public function checkout(Request $request): JsonResponse{
      $data= $this->checkoutService->processOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }



} 
