<?php

namespace App\Controller\Statistic;
use App\Service\Statistic\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'sale history')]
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
    #[OA\Get(
        path:"/api/v1/sale_history/period",
        summary: "Get sales statistics for a specific period",
        description: "Retrieves sales statistics for a specified period such as today, yesterday, this week, this month, or this year.",
        requestBody: (
            new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "period", 
                            type: "string", 
                            example: "this_month",
                            description: "Period to filter sales by (today, yesterday, this_week, this_month, this_year)."
                        )
                    ]
                
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Returns sales statistics for the specified period",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales", type:"number", format: "float", description: "Total sales amount"),
                        new OA\Property(property: "total_purchases", type:"number", format: "float", description: "Total purchases amount"),
                        new OA\Property(property: "profil", type:"number", format: "float", description: "Total profit from sales"),
                        new OA\Property(
                            property: "sales",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "teller", type: "string"),
                                    new OA\Property(
                                        property: "products",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "product_name", type: "string", description: "Name of the product"),
                                                new OA\Property(property: "quantity", type: "integer", description: "Quantity"),
                                                new OA\Property(property: "price", type: "number", format: "float")
                                            ]
                                        )
                                    ),
                                    new OA\Property(property: "date_created", type: "string", format: "date-time", description: "Creation date of the sale"),
                                    new OA\Property(property: "date_update", type: "string", format: "date-time", description: "Last update date of the sale")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No sales data found for the specified period",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", description: "No sales data found for the specified period")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - Missing or invalid 'period' parameter",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Bad request - Missing or invalid 'period' parameter")]
                )
            )
            ]
    )]

    public function getSaleStatistic( Request $request):JsonResponse{
        $data=$this->statistics->getSaleStatistic( $request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/all', name:'app_statistic_all', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
#[OA\Get(
    path: "/api/v1/sale_history/all",
    summary: "Get all sales statistics",
    description: "Retrieves all sales statistics without any filters",
    responses: [
        new OA\Response(
            response: 200, 
            description: "Returns all sales statistics",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "total_sales", type:"number", format: "float", description: "Total sales amount"),
                    new OA\Property(property: "total_purchases", type:"number", format: "float", description: "Total purchases amount"),
                    new OA\Property(property: "profil", type:"number", format: "float", description: "Total profit from sales"),
                    new OA\Property(
                        property: "sales",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "teller", type: "string"),
                                new OA\Property(
                                    property: "products",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "product_name", type: "string", description: "Name of the product"),
                                            new OA\Property(property: "quantity", type: "integer", description: "Quantity"),
                                            new OA\Property(property: "price", type: "number", format: "float")
                                        ]
                                    )
                                ),
                                new OA\Property(property: "date_created", type: "string", format: "date-time", description: "Creation date of the sale"),
                                new OA\Property(property: "date_update", type: "string", format: "date-time", description: "Last update date of the sale")
                            ]
                        )
                    )
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "No sales data found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", description: "No sales data found")
                ]
            )
        )
    ]
)]

        public function getAllSales():JsonResponse{

        $data=$this->statistics->getAllSales();    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }
    
    #[Route('/teller', name: 'app_sales_by_seller', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path:'/api/v1/sale_history/teller',
        summary: "Get sales statistics by seller",
        description: "Retrieves sales statistics for a specific seller",
        requestBody: new OA\RequestBody(
             required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "teller_id", 
                        type: "integer", 
                        description: "ID of the seller to filter sales by"
                    )
                ]
            )
        ),
        responses: [
        new OA\Response(
            response: 200, 
            description: "Returns all sales statistics",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "total_sales", type:"number", format: "float", description: "Total sales amount"),
                    new OA\Property(property: "total_purchases", type:"number", format: "float", description: "Total purchases amount"),
                    new OA\Property(property: "profil", type:"number", format: "float", description: "Total profit from sales"),
                    new OA\Property(
                        property: "sales",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "teller", type: "string"),
                                new OA\Property(
                                    property: "products",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "product_name", type: "string", description: "Name of the product"),
                                            new OA\Property(property: "quantity", type: "integer", description: "Quantity"),
                                            new OA\Property(property: "price", type: "number", format: "float")
                                        ]
                                    )
                                ),
                                new OA\Property(property: "date_created", type: "string", format: "date-time", description: "Creation date of the sale"),
                                new OA\Property(property: "date_update", type: "string", format: "date-time", description: "Last update date of the sale")
                            ]
                        )
                    )
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "No sales data found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", description: "No sales data found")
                ]
            )
        )
    ]
    )]

    public function getSalesBySeller( Request $request):JsonResponse{
    
    $data=$this->statistics->getSalesBySeller($request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/categoryToSale', name: 'app_sales_by_category', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path:"/api/v1/sale_history/categoryToSale",
        summary: "Get sales statistics by category",
        description: "Retrieves sales statistics for a specific category",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties:[
                    new OA\Property(
                        property: "categoryId", 
                        type: "integer", 
                        description: "ID of the category to filter sales by")
                ]
            )
        ),
        responses: [
        new OA\Response(
            response: 200, 
            description: "Returns all sales statistics",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "total_sales", type:"number", format: "float", description: "Total sales amount"),
                    new OA\Property(property: "total_purchases", type:"number", format: "float", description: "Total purchases amount"),
                    new OA\Property(property: "profil", type:"number", format: "float", description: "Total profit from sales"),
                    new OA\Property(
                        property: "sales",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "teller", type: "string"),
                                new OA\Property(
                                    property: "products",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "product_name", type: "string", description: "Name of the product"),
                                            new OA\Property(property: "quantity", type: "integer", description: "Quantity"),
                                            new OA\Property(property: "price", type: "number", format: "float")
                                        ]
                                    )
                                ),
                                new OA\Property(property: "date_created", type: "string", format: "date-time", description: "Creation date of the sale"),
                                new OA\Property(property: "date_update", type: "string", format: "date-time", description: "Last update date of the sale")
                            ]
                        )
                    )
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: "No sales data found",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "error", type: "string", description: "No sales data found")
                ]
            )
        )
    ]
    )]

     public function getSalesByCategory(Request $request):JsonResponse{
        $data=$this->statistics->getSalesByCategory($request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }

    #[Route('/product', name: 'app_sales_by_product', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/sale_history/product",
        summary: "Get sales statistics by product",
        description: "Retrieves sales statistics for a specific product",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "productId", type: "integer", description: "ID of the product to filter sales by")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Returns all sales statistics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales", type:"number", format: "float", description: "Total sales amount"),
                        new OA\Property(property: "total_purchases", type:"number", format: "float", description: "Total purchases amount"),
                        new OA\Property(property: "profil", type:"number", format: "float", description: "Total profit from sales"),
                        new OA\Property(
                            property: "sales",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "teller", type: "string"),
                                    new OA\Property(
                                        property: "products",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "product_name", type: "string", description: "Name of the product"),
                                                new OA\Property(property: "quantity", type: "integer", description: "Quantity"),
                                                new OA\Property(property: "price", type: "number", format: "float")
                                            ]
                                        )
                                    ),
                                    new OA\Property(property: "date_created", type: "string", format: "date-time", description: "Creation date of the sale"),
                                    new OA\Property(property: "date_update", type: "string", format: "date-time", description: "Last update date of the sale")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No sales data found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", description: "No sales data found")
                    ]
                )
            )
        ]
    )]
     public function getSalesByProduct(Request $request):JsonResponse{
        $data=$this->statistics->getSalesByProduct( $request);    
        
        return new JsonResponse([
            'message'=>$data
        ]); 
    }
}
