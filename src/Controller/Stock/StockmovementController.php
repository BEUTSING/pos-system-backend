<?php

namespace App\Controller\Stock;

use App\Entity\Product\Product;
use App\Entity\Stock\Stockmovement;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\StockmovementRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

 #[Route('/stock-movement')]
final class StockmovementController extends AbstractController
{

    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }   


    #[Route('', name: 'app_stockmovement_display', methods: ['GET'])]
    public function display(StockmovementRepository $repo): JsonResponse
    {
        $stockMovements = $repo->findAll();
        return $this->json($stockMovements, Response::HTTP_OK);
    }

    #[Route('', name: 'app_stockmovement_create', methods: ['POST'])]
    public function create(Stockmovement $movement, EntityManagerInterface $em, Request $request,ProductRepository $productrepository): JsonResponse
    {
        $data= json_decode($request->getContent(), true);

         $product = $productrepository->find($data['product']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);}

        $movement=new Stockmovement();
        $movement->setProduct($product);
        $movement->setQuantity($data['quantity']);
        $movement->setTypemovement($data['typemovement']);
        $movement->setReason($data['reason']);


        $em->persist($movement);
        $em->flush();
        $data = [
            'id' => $movement->getId(),
            'product' => $movement->getProduct()->getProductname(),
            'quantity' => $movement->getQuantity(),
            'typemovement' => $movement->getTypemovement(),
            'reason' => $movement->getReason(),
        ];
        // Log the creation of the stock movement
        //$this->logEntryService->createLogEntry('Stock movement created for product: ' . $product->getProductname() . ' with quantity: ' . $movement->getQuantity().' and of type ' .$movement->getTypemovement());
        return $this->json($data, Response::HTTP_CREATED);
    }

#[Route('/{id}', name: 'app_stockmovement_update', methods: ['POST'])]
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
        $movement->setQuantity($data['quantity']??$movement->getQuantity());
        $movement->setTypemovement($data['typemovement']?? $movement->getTypemovement());
        $movement->setReason($data['reason']?? $movement->getReason());

        $em->flush();

        //log the update of the stock movement
    $this->logEntryService->createLogEntry('Stock movement update for product: ' . $product->getProductname() . ' with quantity: ' . $movement->getQuantity());
        return $this->json($movement, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'app_stockmovement_delete', methods: ['DELETE'])]
    public function delete(Stockmovement $movement, EntityManagerInterface $em, Product $product): JsonResponse
    {
        $em->remove($movement);
        $em->flush();

        // log the delete of the stock movement
    $this->logEntryService->createLogEntry('Stock movement delete for product: ' . $product->getProductname());
        return $this->json(['message' => 'Stock movement deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
