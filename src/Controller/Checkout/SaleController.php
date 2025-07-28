<?php

namespace App\Controller\Checkout;

use App\Entity\Checkout\Sale;
use App\Repository\Checkout\SaleRepository;
use App\Repository\Product\ProductRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sale')]
final class SaleController extends AbstractController
{

    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }

    #[Route('/search/{sname}', name: 'app_sale_search', methods: ['GET'])]
    public function search(SaleRepository $repo, string $sname): JsonResponse
    {
        $sales = $repo->findBy(['datesale' => $sname]);
        if (!$sales) {
            return $this->json(['error' => 'Sale not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($sales, Response::HTTP_OK);
    }

    #[Route('', name: 'app_sale_display',methods:['GET'])]
    public function display( SaleRepository $salerepo): JsonResponse
    {

        $sales = $salerepo->findAll();
        $data = [];
        foreach ($sales as $sale) {
            $data[] = [
                'id' => $sale->getId(),
                'product' => $sale->getProduct() ? $sale->getProduct()->getProductname() : null,
                'quantity' => $sale->getQuantity(),
                'datesale' => $sale->getDatesale()->format('Y-m-d H:i:s'),
                'saleprice' => $sale->getSaleprice(),
                'total' => $sale->getTotal(),
            ];
        }
     return $this->json($data, Response::HTTP_OK);
    }

        #[Route('', name: 'app_sale_create',methods:['POST'])]
        public function create(Request $request,EntityManagerInterface $em,ProductRepository $productrepository): JsonResponse {

            $data= json_decode($request->getContent(),true);

        $product = $productrepository->find($data['product']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);}

        $sale=new Sale();
        $sale->setProduct($product);
        $sale->setQuantity($data['quantity']);
        $sale->setDatesale($data['datesale']);
        $sale->setSaleprice($data['saleprice']);
        $sale->setTotal($data['total']);


        $em->persist($sale);
        $em->flush();
        
        $data=[];
        foreach ($sale as $sale) {
            $data[] = [
                'id' => $sale->getId(),
                'product' => $sale->getProduct() ? $sale->getProduct()->getProductname() : null,
                'quantity' => $sale->getQuantity(),
                'datesale' => $sale->getDatesale()->format('Y-m-d H:i:s'),
                'saleprice' => $sale->getSaleprice(),
                'total' => $sale->getTotal(),
            ];
        }

        $this->logEntryService->createLogEntry('Sale created for product: ' . $product->getProductname() . ' with quantity: ' . $sale->getQuantity());
        return $this->json($sale, Response::HTTP_CREATED);
    }

#[Route('/{id}', name: 'app_sale_update', methods: ['POST'])]
    public function update(Sale $sale, EntityManagerInterface $em, Request $request, ProductRepository $productrepository): JsonResponse
    {
        $data= Json_decode($request->getContent(), true);
        
         if(isset($data['product'])){
            $product = $productrepository->find($data['product']);
            if (!$product) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $sale->setProduct($product);
        }
        $sale->setQuantity($data['quantity']??$sale->getQuantity());
        $sale->setDatesale($data['datesale']??$sale->getDatesale());
        $sale->setSaleprice($data['saleprice']??$sale->getSaleprice());
        $sale->setTotal($data['total']??$sale->getTotal());
       

        $em->flush();
        return $this->json($sale, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'app_sale_delete', methods: ['DELETE'])]
    public function delete(Sale $sale, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($sale);
        $em->flush();

        // Log the deletion of the sale
        $this->logEntryService->createLogEntry('Sale deleted for product: ' . $sale->getProduct()->getProductname() . ' with quantity: ' . $sale->getQuantity());
        return $this->json(['message' => 'sale deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
