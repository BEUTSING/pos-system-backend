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
    path:"/api/v1/checkout/order",
    summary:"Process an order",
    description:"Allows you to create a new order or add items to an existing order",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "customerOrderId", type: "integer", nullable: true, example: 123),
                new OA\Property(
                    property: "items",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "product_id", type: "integer", example: 1),
                            new OA\Property(property: "quantity", type: "integer", example: 2)
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Order processed successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "Succes", type: "string", example: "true"),
                    new OA\Property(
                        property: "message",
                        type: "object",
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Order processed successfully"),
                            new OA\Property(property: "order_id", type: "integer", example: 123),
                            new OA\Property(property: "waiter_id", type: "integer", example: 456),
                            new OA\Property(property: "total_amount", type: "number", format: "float", example: 50.5),
                            new OA\Property(
                                property: "items",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "order_item_id", type: "integer"),
                                        new OA\Property(property: "product_id", type: "integer"),
                                        new OA\Property(property: "product_name", type: "string"),
                                        new OA\Property(property: "quantity", type: "integer"),
                                        new OA\Property(property: "price", type: "number", format: "float"),
                                        new OA\Property(property: "subtotal", type: "number", format: "float"),
                                        new OA\Property(property: "date_create", type: "string", format: "date-time"),
                                        new OA\Property(property: "date_update", type: "string", format: "date-time")
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Bad Request - Invalid data",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "No items provided or insufficient quantity")
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Not Found - Order or product not found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "Customer order not found or Product not found")
                ]
            )
        )
    ]
  )]
    

  public function Order(Request $request): JsonResponse{
    try {
      $data= $this->checkoutService->processOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
    } catch (\Exception $e) {
        return new JsonResponse([
          "statut"=> "false",
            "error" => $e->getMessage()
        ], 400);
      }
  }

  #[Route('/sale', name:'app_checkout_sale',methods:['POST'] )]
  #[IsGranted(attribute: 'ROLE_TELLER')]
  #[OA\Post(
      path:"/api/v1/checkout/sale",
      summary:"Create a sale from an order",
      description:"Allows you to create a sale from an existing customer order",
      requestBody: new OA\RequestBody(
          required: true,
          content: new OA\JsonContent(
              type: "object",
              properties: [
                  new OA\Property(property: "customerOrderId", type: "integer", example:"1")
              ]
          )
      ),
      responses: [
          new OA\Response(
              response: 200,
              description: "Sale created successfully",
              content: new OA\JsonContent(
                  properties: [
                      new OA\Property(property: "Succes", type: "string", example: "true"),
                      new OA\Property(property: "message", type: "object",
                          properties: [
                              new OA\Property(property: "sale_id", type: "integer", example: 1),
                              new OA\Property(property: "statut", type: "string", example: "PENDING", nullable: true),
                              new OA\Property(property: "teller", type: "string", example:"John Doe"),
                              new OA\Property(property: "order_id", type: "integer", example: 123),
                              new OA\Property(property: "total_amount", type: "number", format: "float", example: 50.5),
                              new OA\Property(property: "payment_method", type: "string", example: "Cash"),
                              new OA\Property(
                                  property: "items",
                                  type: "array",
                                  items: new OA\Items(
                                      properties: [
                                          new OA\Property(property: "product_id", type: "integer"),
                                          new OA\Property(property: "product_name", type: "string"),
                                          new OA\Property(property: "quantity", type: "integer"),
                                          new OA\Property(property: "price", type: "number", format: "float"),
                                          new OA\Property(property: "subtotal", type: "number", format: "float"),
                                          new OA\Property(property: "date_create", type: "string", format: "date-time"),
                                          new OA\Property(property: "date_update", type: "string", format: "date-time")
                                      ]
                                  )
                              ),
                              new OA\Property(property: "invoice", type: "string", nullable: true, example: "copy")
                          ]
                      )
                  ]
              )
          ),
          new OA\Response(
              response: 404,
              description: "Not Found - Order not found",
              content: new OA\JsonContent(
                  properties: [
                      new OA\Property(property: "error", type: "string", example: "Order not found")
                  ]
              )
          )
      ]
    )]

  public function sale(Request $request): JsonResponse{
      try {
      $data= $this->checkoutService->createSaleFromOrder($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
    } catch (\Exception $se) {
        return new JsonResponse([
          "statut"=> "false",
            "error" => $se->getMessage()
        ], 400);
      }
  }

#[Route('/cancel', name:'cancel',methods:['POST'] )]
#[IsGranted(attribute: 'ROLE_MANAGER')]
#[OA\Post(
  path:'/api/v1/checkout/cancel',
      summary: "Cancel an order item or update its quantity",
    description: "Allows a manager to remove a specific item from an order by setting the quantity to 0 or by omitting the quantity field.",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "orderItemId", type: "integer", example: 1),
                new OA\Property(property: "quantity", type: "integer", nullable: true, example: 0)
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Order item cancelled or quantity updated successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "Succes", type: "string", example: "true"),
                    new OA\Property(
                        property: "message",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "itemid", type: "integer"),
                                new OA\Property(property: "orderid", type: "integer"),
                                new OA\Property(
                                    property: "items",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "product_id", type: "integer"),
                                            new OA\Property(property: "product_name", type: "string"),
                                            new OA\Property(property: "price", type: "number", format: "float"),
                                            new OA\Property(property: "new_quantity_item", type: "integer"),
                                            new OA\Property(property: "date_create", type: "string", format: "date-time"),
                                            new OA\Property(property: "date_update", type: "string", format: "date-time")
                                        ]
                                    )
                                )
                            ]
                        )
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Bad Request - Invalid quantity or insufficient stock",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "Quantity must be greater than zero or insufficient quantity")
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Not Found - Order item not found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "The order item was not found.")
                ]
            )
        )
    ]
)]
    public function cancel(Request $request): JsonResponse{
      try {
      $data= $this->checkoutService->orderItemCancellation($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
    } catch (\Exception $e) {
        return new JsonResponse([
          "statut"=> "false",
            "error" => $e->getMessage()
        ], 400);
      }
    }
  

  #[Route('/cancelsale', name:'slcancel',methods:['POST'] )]
  #[IsGranted(attribute: 'ROLE_MANAGER')]
  #[OA\Post(
    path:'/api/v1/checkout/cancelsale',
    summary: "Cancel a sale",
    description: "Allows you to cancel a sale by providing the sale ID and reason for cancellation",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "saleId", type: "integer", example: 1),
                new OA\Property(property: "reason", type: "string", example: "Customer request")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Sale cancelled successfully",
            content: new OA\JsonContent(
                properties:[
                  new OA\Property(property:"message", type: "object",
                  properties:[
                    new OA\Property(property: "id_cancel", type: "integer", example: 1),
                    new OA\Property(property:"cancelled_by", type:"object", example:"John Doe"),
                    new OA\Property(property: "reason", type: "string", example: "Customer request"),
                    new OA\Property(property: "sale_id", type: "integer", example: 1),
                    new OA\Property(property: "status", type: "string", example: "Cancelled"),
                    new OA\Property(property: "order_id", type: "integer"),
                    new OA\Property(property: "total_amount", type: "number", format: "float"),
                    new OA\Property(property: "payment_method", type: "string"),

                  ])
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Bad Request - Sale already cancelled",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "This sale has already been cancelled.")
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "Not Found - Sale not found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", example: "Sale not found")
                ]
            )
        )
    ]
  )]
 
  public function cancelSale(Request $request): JsonResponse{
      try {
      $data= $this->checkoutService->cancelSale($request);

      return new jsonResponse([
        "Succes"=>"true",
        "message"=>$data
      ]);
  }catch(\Exception $e){
      return new JsonResponse([
        "statut"=> "false",
        "error" => $e->getMessage()
      ], 400);
    } 
  }
    
} 
