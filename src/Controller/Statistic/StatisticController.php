<?php

namespace App\Controller\Statistic;

use App\Service\Statistic\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    // ─── PERIOD: get sales statistics filtered by period ─────────────────────────
    #[Route('/period', name: 'app_statistic_period', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/sale_history/period",
        summary: "Get sales statistics for a specific period",
        description: "Retrieves sales for: today, yesterday, this_week, this_month, this_year — filtered by the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "period",
                        type: "string",
                        example: "this_month",
                        description: "Allowed: today, yesterday, this_week, this_month, this_year"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Sales statistics for the specified period",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales",     type: "number", format: "float"),
                        new OA\Property(property: "total_purchases", type: "number", format: "float"),
                        new OA\Property(property: "profit",          type: "number", format: "float"),
                        new OA\Property(property: "sales",           type: "array",  items: new OA\Items(type: "object")),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Missing or invalid period"),
            new OA\Response(response: 404, description: "No sales found for this period"),
        ]
    )]
    public function getSaleStatistic(Request $request): JsonResponse
    {
        try {
            $data = $this->statistics->getSaleStatistic($request);
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── ALL: get all sales of the current company ────────────────────────────────
    #[Route('/all', name: 'app_statistic_all', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/sale_history/all",
        summary: "Get all sales",
        description: "Retrieves all sales filtered by the current company",
        responses: [
            new OA\Response(
                response: 200,
                description: "All sales statistics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales",     type: "number", format: "float"),
                        new OA\Property(property: "total_purchases", type: "number", format: "float"),
                        new OA\Property(property: "profit",          type: "number", format: "float"),
                        new OA\Property(property: "sales",           type: "array",  items: new OA\Items(type: "object")),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "No sales found"),
        ]
    )]
    public function getAllSales(): JsonResponse
    {
        try {
            $data = $this->statistics->getAllSales();
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── TELLER: get sales filtered by a specific teller ─────────────────────────
    #[Route('/teller', name: 'app_sales_by_seller', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: '/api/v1/sale_history/teller',
        summary: "Get sales by teller",
        description: "Retrieves sales for a specific teller within the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "teller_id", type: "integer", description: "ID of the teller")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Sales by teller",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales",     type: "number", format: "float"),
                        new OA\Property(property: "total_purchases", type: "number", format: "float"),
                        new OA\Property(property: "profit",          type: "number", format: "float"),
                        new OA\Property(property: "sales",           type: "array",  items: new OA\Items(type: "object")),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "No sales found for this teller"),
        ]
    )]
    public function getSalesBySeller(Request $request): JsonResponse
    {
        try {
            $data = $this->statistics->getSalesBySeller($request);
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── CATEGORY: get sales filtered by product category ────────────────────────
    #[Route('/categoryToSale', name: 'app_sales_by_category', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/sale_history/categoryToSale",
        summary: "Get sales by category",
        description: "Retrieves sales for a specific product category within the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "categoryId", type: "integer", description: "ID of the category")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Sales by category",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales",     type: "number", format: "float"),
                        new OA\Property(property: "total_purchases", type: "number", format: "float"),
                        new OA\Property(property: "profit",          type: "number", format: "float"),
                        new OA\Property(property: "sales",           type: "array",  items: new OA\Items(type: "object")),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "categoryId is required"),
            new OA\Response(response: 404, description: "No sales found for this category"),
        ]
    )]
    public function getSalesByCategory(Request $request): JsonResponse
    {
        try {
            $data = $this->statistics->getSalesByCategory($request);
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── PRODUCT: get sales filtered by a specific product ───────────────────────
    #[Route('/product', name: 'app_sales_by_product', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/sale_history/product",
        summary: "Get sales by product",
        description: "Retrieves sales for a specific product within the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "productId", type: "integer", description: "ID of the product")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Sales by product",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_sales",     type: "number", format: "float"),
                        new OA\Property(property: "total_purchases", type: "number", format: "float"),
                        new OA\Property(property: "profit",          type: "number", format: "float"),
                        new OA\Property(property: "sales",           type: "array",  items: new OA\Items(type: "object")),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Product not found or no sales"),
        ]
    )]
    public function getSalesByProduct(Request $request): JsonResponse
    {
        try {
            $data = $this->statistics->getSalesByProduct($request);
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}