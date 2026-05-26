<?php

namespace App\Controller\Stock;

use App\Entity\Product\Product;
use App\Entity\Stock\Stockmovement;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\StockmovementRepository;
use App\Service\Company\CompanyService;

use App\Enum\ReasonMovement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'stock movement')]
#[Route('/stock-movement')]
final class StockmovementController extends AbstractController
{

    private CompanyService $companyService;

    public function __construct(
        private Security $security,
        CompanyService $companyService
    ) {
        $this->companyService = $companyService;
    }

    // ─── LIST: retrieve all stock movements of the current company ───────────────
    #[Route('/list', name: 'app_stockmovement_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/stock-movement/list",
        summary: "List all stock movements",
        description: "Returns all stock movements belonging to the current company",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of stock movements",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",           type: "integer", example: 1),
                            new OA\Property(property: "product",      type: "string",  example: "Product Name"),
                            new OA\Property(property: "quantity",     type: "integer", example: 1),
                            new OA\Property(property: "typemovement", type: "string",  example: "in"),
                            new OA\Property(property: "reason",       type: "string",  example: "Restock"),
                            new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                            new OA\Property(property: "createdAt",    type: "string",  format: "date-time", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "updatedAt",    type: "string",  format: "date-time", example: "2023-10-01T12:00:00Z"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No stock movements found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "Status", type: "string", example: "no stock movements registered")]
                )
            )
        ]
    )]
    public function list(StockmovementRepository $repo): JsonResponse
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Filter stock movements by the current company only — not findAll()
        $stockMovements = $repo->findBy(['company' => $company]);
        if (!$stockMovements) {
            return $this->json(['Status' => 'No stock movements found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($stockMovements as $movement) {
            $data[] = [
                'id'           => $movement->getId(),
                'product'      => $movement->getProduct()?->getProductname(),
                'quantity'     => $movement->getQuantity(),
                'typemovement' => $movement->getTypemovement(),
                'reason'       => $movement->getReason(),
                'company'      => $movement->getCompany()?->getNameComp(),
                'createdAt'    => $movement->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt'    => $movement->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── SEARCH BY PRODUCT NAME: find movements by product name within the current company ──
    #[Route('/search/product/{pname}', name: 'app_stockmovement_search_product', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/stock-movement/search/product/{pname}",
        summary: "Search stock movements by product name",
        description: "Returns all stock movements for a product matching the name within the current company",
        parameters: [
            new OA\Parameter(
                name: "pname",
                in: "path",
                required: true,
                description: "Product name to search for",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Stock movements found",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",           type: "integer", example: 1),
                            new OA\Property(property: "product",      type: "string",  example: "Product Name"),
                            new OA\Property(property: "quantity",     type: "integer", example: 5),
                            new OA\Property(property: "typemovement", type: "string",  example: "in"),
                            new OA\Property(property: "reason",       type: "string",  example: "transfer_in"),
                            new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                            new OA\Property(property: "createdAt",    type: "string",  example: "2023-10-01T12:00:00Z"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: "No movements found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "No movements found for this product")]
                )
            )
        ]
    )]
    public function searchByProduct(StockmovementRepository $repo, string $pname): JsonResponse
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Search movements by product name within the current company
        $movements = $repo->findByProductNameAndCompany($pname, $company);
        if (!$movements) {
            return $this->json(['error' => 'No movements found for this product'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($movements as $movement) {
            $data[] = [
                'id'           => $movement->getId(),
                'product'      => $movement->getProduct()?->getProductname(),
                'quantity'     => $movement->getQuantity(),
                'typemovement' => $movement->getTypemovement(),
                'reason'       => $movement->getReason(),
                'company'      => $movement->getCompany()?->getNameComp(),
                'createdAt'    => $movement->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt'    => $movement->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── SEARCH BY TYPE: find movements by type (in/out) within the current company ──
    #[Route('/search/type/{type}', name: 'app_stockmovement_search_type', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/stock-movement/search/type/{type}",
        summary: "Search stock movements by type",
        description: "Returns all stock movements of a given type (in or out) within the current company",
        parameters: [
            new OA\Parameter(
                name: "type",
                in: "path",
                required: true,
                description: "Movement type: 'in' or 'out'",
                schema: new OA\Schema(type: "string", enum: ["in", "out"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Stock movements found",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id",           type: "integer", example: 1),
                            new OA\Property(property: "product",      type: "string",  example: "Product Name"),
                            new OA\Property(property: "quantity",     type: "integer", example: 5),
                            new OA\Property(property: "typemovement", type: "string",  example: "in"),
                            new OA\Property(property: "reason",       type: "string",  example: "transfer_in"),
                            new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                            new OA\Property(property: "createdAt",    type: "string",  example: "2023-10-01T12:00:00Z"),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid movement type",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Invalid type. Use 'in' or 'out'")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No movements found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "No movements found for this type")]
                )
            )
        ]
    )]
    public function searchByType(StockmovementRepository $repo, string $type): JsonResponse
    {
        // Validate movement type
        if (!in_array($type, ['in', 'out'])) {
            return $this->json(['error' => "Invalid type. Use 'in' or 'out'"], Response::HTTP_BAD_REQUEST);
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Filter movements by type and current company — findBy handles both conditions
        $movements = $repo->findBy(['typemovement' => $type, 'company' => $company]);
        if (!$movements) {
            return $this->json(['error' => 'No movements found for this type'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($movements as $movement) {
            $data[] = [
                'id'           => $movement->getId(),
                'product'      => $movement->getProduct()?->getProductname(),
                'quantity'     => $movement->getQuantity(),
                'typemovement' => $movement->getTypemovement(),
                'reason'       => $movement->getReason(),
                'company'      => $movement->getCompany()?->getNameComp(),
                'createdAt'    => $movement->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt'    => $movement->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    // ─── CREATE: create a new stock movement and assign it to the current company ─
    #[Route('/create', name: 'app_stockmovement_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/stock-movement/create",
        summary: "Create a new stock movement",
        description: "Creates a stock movement and automatically assigns it to the current company",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "product",      type: "integer", example: 1),
                    new OA\Property(property: "quantity",     type: "integer", example: 5),
                    new OA\Property(property: "typemovement", type: "string",  example: "in"),
                    new OA\Property(property: "reason",       type: "string",  example: "transfer_in"),
                ]
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Stock movement created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",           type: "integer", example: 1),
                        new OA\Property(property: "user",         type: "integer", example: 1),
                        new OA\Property(property: "product",      type: "string",  example: "Product Name"),
                        new OA\Property(property: "quantity",     type: "integer", example: 5),
                        new OA\Property(property: "typemovement", type: "string",  example: "in"),
                        new OA\Property(property: "reason",       type: "string",  example: "transfer_in"),
                        new OA\Property(property: "old_quantity", type: "integer", example: 100),
                        new OA\Property(property: "new_quantity", type: "integer", example: 105),
                        new OA\Property(property: "company",      type: "string",  example: "Acme Corp"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - missing fields or invalid reason",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Missing required fields")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Product not found")]
                )
            )
        ]
    )]
    public function create(EntityManagerInterface $em, Request $request, ProductRepository $productrepository): JsonResponse
    {
        // ← fixed: removed Stockmovement $movement from params (Doctrine tried to find it by id)
        $data = json_decode($request->getContent(), true);

        $required = ['product', 'quantity', 'typemovement', 'reason'];
        foreach ($required as $field) {
            if (empty($data[$field]) || !isset($data[$field])) {
                return $this->json(['error' => "The field $field is required"], Response::HTTP_BAD_REQUEST);
            }
        }

        $user = $this->security->getUser();

        $product = $productrepository->find($data['product']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            return $this->json(['error' => 'No company assigned to the authenticated user'], Response::HTTP_NOT_FOUND);
        }

        // Validate reason against the enum
        $allowedReason = array_column(ReasonMovement::cases(), 'value');
        if (!in_array($data['reason'], $allowedReason)) {
            return new JsonResponse([
                'error' => 'Invalid reason: ' . $data['reason'] . '. Allowed reasons: ' . implode(', ', $allowedReason)
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check sufficient stock for outgoing movements
        if ($data['typemovement'] === 'out' && $data['quantity'] > $product->getQuantity()) {
            return $this->json(['error' => 'Insufficient stock for this product'], Response::HTTP_BAD_REQUEST);
        }

        $oldQuantity = $product->getQuantity();

        $movement = new Stockmovement();
        $movement->setQuantity($data['quantity']);
        $movement->setUser($user);
        $movement->setTypemovement($data['typemovement']);
        $movement->setReason($data['reason']);
        $movement->setProduct($product);
        $movement->setCompany($company); // ← automatically assigned from the current session

        // Update product stock quantity
        switch ($data['typemovement']) {
            case 'in':
                $product->setQuantity($oldQuantity + $data['quantity']);
                break;
            case 'out':
                $product->setQuantity($oldQuantity - $data['quantity']);
                break;
            default:
                return $this->json(['error' => 'Invalid movement type'], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($movement);
        $em->persist($product);
        $em->flush();

        // $this->logEntryService->createLogEntry(
        //     'Stock movement created for product: ' . $product->getProductname() .
        //     ' with quantity: ' . $movement->getQuantity() .
        //     ' and of type ' . $movement->getTypemovement()
        // );

        return $this->json([
            'id'           => $movement->getId(),
            'user'         => $movement->getUser()->getId(),
            'product'      => $product->getProductname(),
            'quantity'     => $movement->getQuantity(),
            'typemovement' => $movement->getTypemovement(),
            'reason'       => $movement->getReason(),
            'old_quantity' => $oldQuantity,
            'new_quantity' => $product->getQuantity(),
            'company'      => $company->getNameComp(),
        ], Response::HTTP_CREATED);
    }

    // ─── UPDATE: update an existing stock movement ───────────────────────────────
    #[Route('/modify/{id}', name: 'app_stockmovement_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
        path: "/api/v1/stock-movement/modify/{id}",
        summary: "Modify an existing movement",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the movement to modify",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "product",      type: "integer", example: 1),
                    new OA\Property(property: "quantity",     type: "integer", example: 5),
                    new OA\Property(property: "typemovement", type: "string",  example: "in"),
                    new OA\Property(property: "reason",       type: "string",  example: "transfer_in"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Movement updated successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "Stock movement updated successfully")]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid data",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Invalid movement type")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Product not found")]
                )
            )
        ]
    )]
    public function update(Stockmovement $movement, EntityManagerInterface $em, Request $request, ProductRepository $productrepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true); // ← fixed: Json_decode → json_decode

        if (isset($data['product'])) {
            $product = $productrepository->find($data['product']);
            if (!$product) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $movement->setProduct($product);
        }

        // Validate reason against the enum if provided
        if (isset($data['reason'])) {
            $allowedReason = array_column(ReasonMovement::cases(), 'value');
            if (!in_array($data['reason'], $allowedReason)) {
                return new JsonResponse([
                    'error' => 'Invalid reason: ' . $data['reason'] . '. Allowed reasons: ' . implode(', ', $allowedReason)
                ], Response::HTTP_BAD_REQUEST);
            }
            $movement->setReason($data['reason']);
        }

        if (isset($data['quantity']))     $movement->setQuantity($data['quantity']);
        if (isset($data['typemovement'])) $movement->setTypemovement($data['typemovement']);

        $em->flush();

        // $this->logEntryService->createLogEntry(
        //     'Stock movement updated for product: ' . $movement->getProduct()->getProductname()
        // );

        return $this->json(['message' => 'Stock movement updated successfully'], Response::HTTP_OK);
    }

    // ─── DELETE: remove a stock movement by ID ───────────────────────────────────
    #[Route('/delete/{id}', name: 'app_stockmovement_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Delete(
        path: "/api/v1/stock-movement/delete/{id}",
        summary: "Delete a stock movement",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "The ID of the movement to delete",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Stock movement deleted successfully"),
            new OA\Response(
                response: 404,
                description: "Movement not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Stock movement not found")]
                )
            )
        ]
    )]
    public function delete(Stockmovement $movement, EntityManagerInterface $em): JsonResponse
    {
        // ← fixed: removed Product $product from params — not needed, get it from movement
        // $this->logEntryService->createLogEntry(
        //     'Stock movement deleted for product: ' . $movement->getProduct()->getProductname()
        // );

        $em->remove($movement);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}