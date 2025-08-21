<?php

namespace App\Controller\Stock;

use App\Entity\Product\Product;
use App\Entity\Stock\Stockmovement;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\StockmovementRepository;
use App\Service\LogEntryService;
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

    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }   


    #[Route('/list', name: 'app_stockmovement_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Get(
        path: "/api/v1/stock-movement/list",
        summary: "List all stock movements",
        description: "Returns a list of all stock movements",
        responses: [
            new OA\Response(
                response: 200,
                description: "List of stock movements",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "product", type: "string", example: "Product Name"),
                            new OA\Property(property: "quantity", type: "integer", example:1),
                            new OA\Property(property: "typemovement", type: "string", example: "in"),
                            new OA\Property(property: "reason", type: "string", example: "Restock"),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2023-10-01T12:00:00Z"),
                            new OA\Property(property: "updatedAt", type: "string", format: "date-time", example: "2023-10-01T12:00:00Z"),
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

    public function display(StockmovementRepository $repo): JsonResponse
    {
        $stockMovements = $repo->findAll();
        $data = [];
        foreach ($stockMovements as $movement) {
            $data[] = [
                'id' => $movement->getId(),
                'product' => $movement->getProduct() ? $movement->getProduct()->getProductname() : null,
                'quantity' => $movement->getQuantity(),
                'typemovement' => $movement->getTypemovement(),
                'reason' => $movement->getReason(),
                'createdAt' => $movement->getCreatedAt()->format('Y-m-d H:i:s'),
                'upadateAt' => $movement->getUpdatedAt()->format('Y-m-d H:i:s'),];
        }
        return $this->json($data, Response::HTTP_OK);
    }
   
    #[Route('/create', name: 'app_stockmovement_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Post(
        path: "/api/v1/stock-movement/create",
        summary: "Create a new stock movement",
        description: "Allows creating a new stock movement",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "product", type: "integer", example: 1),
                    new OA\Property(property:"quantity", type:"integer", example:5),
                    new OA\Property(property: "typemovement", type: "string", example: "in"),
                    new OA\Property(property: "reason", type: "string", example: "transfer_in"),
                ]
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description:"stock movement created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "user", type: "integer", example: 1),
                        new OA\Property(property: "product", type: "integer", example: 1),
                        new OA\Property(property: "quantity", type: "integer", example: 5),
                        new OA\Property(property: "typemovement", type: "string", example: "in"),
                        new OA\Property(property: "reason", type: "string", example: "transfer_in"),
                        new OA\Property(property: "old_quantity", type: "integer", example: 100),
                        new OA\Property(property: "new_quantity", type: "integer", example: 105), 
                        new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z"),
                        new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - missing fields or reason invalid",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "Missing required fields or reason invalid")]
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

    public function create(Stockmovement $movement, EntityManagerInterface $em, Request $request,ProductRepository $productrepository): JsonResponse
    {
        $data= json_decode($request->getContent(), true);
        $user = $this->security->getUser();


         $product = $productrepository->find($data['product']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);}

        $movement=new Stockmovement();
        $movement->setQuantity($data['quantity']);
        $movement->setUser($user);
        $movement->setTypemovement($data['typemovement']);
        if(isset($data['reason'])){
                $allwedReason=array_column(ReasonMovement::cases(), 'value');
                    if( in_array($data['reason'],$allwedReason)){
                        $movement->setReason($data['reason']);
                    }else{
                        return new JsonResponse([
                            'error' => 'Invalid reason: ' . $data['reason'] . '. Allowed reasons: ' . implode(', ', $allwedReason)
                        ], Response::HTTP_BAD_REQUEST);
                    }
                
            }
        if($movement->getTypemovement()=='out'){
                
                if($data['quantity'] > $product->getQuantity()){
                    return $this->json(['error' => 'Insufficient stock for this product'], Response::HTTP_BAD_REQUEST);
                }
            
            }
        
        switch ($movement->getTypemovement()) {
            case 'in':
                $product->setQuantity($product->getQuantity() + $movement->getQuantity());
                break;
            case 'out':
                $product->setQuantity($product->getQuantity() - $movement->getQuantity());
                break;
            default:
                return $this->json(['error' => 'Invalid movement type'], Response::HTTP_BAD_REQUEST);
        }
        $em->persist($product);
        $movement->setProduct($product);
        $em->persist($movement);
        $em->flush();
        $data = [
            'id' => $movement->getId(),
            'user'=>$movement->getUser()->getId(),
            'product' => $movement->getProduct()->getProductname(),
            'quantity' => $movement->getQuantity(),
            'typemovement' => $movement->getTypemovement(),
            'reason' => $movement->getReason(),
            'old_quantity' => $product->getQuantity() - $movement->getQuantity(),
            'new_quantity' => $product->getQuantity(),
            
        ];
        // Log the creation of the stock movement
        $this->logEntryService->createLogEntry('Stock movement created for product: ' . $product->getProductname() . ' with quantity: ' . $movement->getQuantity().' and of type ' .$movement->getTypemovement());
        return $this->json($data, Response::HTTP_CREATED);
    }

    #[Route('/modify/{id}', name: 'app_stockmovement_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
    #[OA\Put(
    path:"/api/v1/stock-movement/modify/{id}",
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
        content:new OA\JsonContent(
            properties:[
                new OA\Property(property: "product", type: "integer", example: 1),
                new OA\Property(property:"quantity", type:"integer", example:5),
                new OA\Property(property: "typemovement", type: "string", example: "in"),
                new OA\Property(property: "reason", type: "string", example: "transfer_in"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Movement updated successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "product", type: "integer", example: 2),
                    new OA\Property(property: "quantity", type: "integer", example: 5),
                    new OA\Property(property: "typemovement", type: "string", example: "in"),
                    new OA\Property(property: "reason", type: "string", example: "transfer_in"),
                    new OA\Property(property: "old_quantity", type: "integer", example: 100),
                    new OA\Property(property: "new_quantity", type: "integer", example: 105),
                    new OA\Property(property: "date_updateAt", type: "string", example: "2023-10-01T12:00:00Z"),
                    new OA\Property(property: "date_createAt", type: "string", example: "2023-10-01T12:00:00Z")

                ]
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
        $data= Json_decode($request->getContent(), true);
        
         if(isset($data['product'])){
            $product = $productrepository->find($data['product']);
            if (!$product) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $movement->setProduct($product);
        }
        if(isset($data['reason'])){
                $allwedReason=array_column(ReasonMovement::cases(), 'value');
                    if( in_array($data['reason'],$allwedReason)){
                        $reason=$data['reason'];
                    }else{
                        return new JsonResponse([
                            'error' => 'Invalid reason: ' . $data['reason'] . '. Allowed reasons: ' . implode(', ', $allwedReason)
                        ], Response::HTTP_BAD_REQUEST);
                    }
                
            }
        $movement->setQuantity($data['quantity']??$movement->getQuantity());
        $movement->setTypemovement($data['typemovement']?? $movement->getTypemovement());
        $movement->setReason($reason?? $movement->getReason());

        $em->flush();

        //log the update of the stock movement
    $this->logEntryService->createLogEntry('Stock movement update for product: ' . $product->getProductname() . ' with quantity: ' . $movement->getQuantity());
        return $this->json($movement, Response::HTTP_OK);
    }



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
            new OA\Response(
                response: 200,
                description: "stock movement deleted successfully",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string", example: "stock movement deleted successfully")]
                )
            ),
            new OA\Response(
                response: 404,
                description: "movement not found",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "error", type: "string", example: "stock movement not found")]
                )
            )
        ]
    )]
    public function delete(Stockmovement $movement, EntityManagerInterface $em, Product $product): JsonResponse
    {
        $em->remove($movement);
        $em->flush();

        // log the delete of the stock movement
    $this->logEntryService->createLogEntry('Stock movement delete for product: ' . $product->getProductname());
        return $this->json(['message' => 'Stock movement deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
