<?php

namespace App\Controller\Checkout;

use App\Service\Checkout\CheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA; 

#[OA\Tag(name: 'Chockout')]
#[Route('/checkout')]  
final class CheckoutController extends AbstractController
{

    private $checkoutService;
    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService=$checkoutService;
        
    }

  #[Route('/order', name:'app_checkout_order',methods:['POST'] )]
  #[IsGranted(attribute: 'ROLE_WAITER')]
  #[OA\Post(
    path:"/checkout/order",
    summary:"Process an order",
    description:"Allows you to create a new order or add items to an existing order
"  
  )]

  public function Order(Request $request): JsonResponse{
      $data= $this->checkoutService->processOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

  #[Route('/sale', name:'app_checkout_sale',methods:['POST'] )]
  #[IsGranted(attribute: 'ROLE_TELLER')]
  public function sale(Request $request): JsonResponse{
      $data= $this->checkoutService->createSaleFromOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

#[Route('/cancel', name:'cancel',methods:['POST'] )]
#[IsGranted(attribute: 'ROLE_MANAGER')]

    public function cancel(Request $request): JsonResponse{
      $data= $this->checkoutService->orderItemCancellation($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }

  #[Route('/cancelsale', name:'slcancel',methods:['POST'] )]
  #[IsGranted(attribute: 'ROLE_MANAGER')]
 
  public function cancelSale(Request $request): JsonResponse{
      $data= $this->checkoutService->cancelSale($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }
    
} 
