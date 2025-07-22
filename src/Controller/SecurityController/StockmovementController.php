<?php

namespace App\Controller\SecurityController;

use App\Entity\Stock\Stockmovement;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\StockmovementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

 #[Route('/api/stock-movement')]
final class StockmovementController extends AbstractController
{

    
    #[Route('', name: 'app_display', methods: ['GET'])]
    public function display(StockmovementRepository $repo): JsonResponse
    {
        $stockMovements = $repo->findAll();
        return $this->json($stockMovements, Response::HTTP_OK);
    }

    #[Route('', name: 'app_create', methods: ['POST'])]
    public function create(Stockmovement $movement, EntityManagerInterface $em, Request $request,ProductRepository $productRepository): JsonResponse
    {
        $data= json_decode($request->getContent(), true);

         $product = $productRepository->find($data['product']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);}

        $movement=new Stockmovement();
        $movement->setProduct($product);
        $movement->setQuantity($data['quantity']);
        $movement->setTypemovement($data['typemovement']);


        $em->persist($movement);
        $em->flush();
        return $this->json($movement, Response::HTTP_CREATED);
    }

#[Route('/{id}', name: 'app_update', methods: ['POST'])]
    public function update(Stockmovement $movement, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data= Json_decode($request->getContent(), true);

        $movement->setProduct($data['product']?? $movement->getProduct());
        $movement->setQuantity($data['quantity']??$movement->getQuantity());
        $movement->setTypemovement($data['typemovement']?? $movement->getTypemovement());

        $em->persist($movement);
        $em->flush();
        return $this->json($movement, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'app_delete', methods: ['DELETE'])]
    public function delete(Stockmovement $movement, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($movement);
        $em->flush();
        return $this->json(['message' => 'Stock movement deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
