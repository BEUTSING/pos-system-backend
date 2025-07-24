<?php

namespace App\Controller\Stock;

use App\Entity\Stock\Supplier;
use App\Repository\Stock\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier')]
final class SupplierController extends AbstractController
{
    #[Route('', name: 'app_supplier_display', methods: ['GET'])]
    public function display(SupplierRepository $supplierRepository): JsonResponse
    {
        return $this->json($supplierRepository->findAll(), Response::HTTP_OK);
    }


    #[Route('/search/{sname}', name: 'app_supplier_search', methods: ['GET'])]
    public function search(SupplierRepository $supplierRepository, string $sname): JsonResponse
    {
        $suppliers = $supplierRepository->findsupplier($sname);
        if (!$suppliers) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($suppliers, Response::HTTP_OK);
}

    #[Route('', name: 'app_supplier_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $supplier = new Supplier();
        $supplier->setName($data['name']);
        $supplier->setEmail($data['email']);
        $supplier->setCity($data['city']);
        $supplier->setPhone($data['phone']);
        
        $em->persist($supplier);
        $em->flush();
        return $this->json(['message' => 'Supplier created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_supplier_update', methods: ['PUT'])]
    public function update(Supplier $supplier, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $supplier->setName($data['name'] ?? $supplier->getName());
        $supplier->setEmail($data['email'] ?? $supplier->getEmail());
        $supplier->setCity($data['city'] ?? $supplier->getCity());
        $supplier->setPhone($data['phone'] ?? $supplier->getPhone());

        $em->flush();
        return $this->json(['message' => 'Supplier updated successfully'], Response::HTTP_OK);
    
    }

    #[Route('/{id}', name: 'app_supplier_delete', methods: ['DELETE'])]
    public function delete(Supplier $supplier, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($supplier);
        $em->flush();
        return $this->json(['message' => 'Supplier deleted successfully'], Response::HTTP_NO_CONTENT);
    }

}
