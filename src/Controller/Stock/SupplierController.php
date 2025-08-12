<?php

namespace App\Controller\Stock;

use App\Entity\Stock\Supplier;
use App\Repository\Stock\SupplierRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/supplier')]
final class SupplierController extends AbstractController

{
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }
    
    #[Route('/list', name: 'app_supplier_display', methods: ['GET'])]
    public function display(SupplierRepository $supplierRepository): JsonResponse
    {
        return $this->json($supplierRepository->findAll(), Response::HTTP_OK);
    }


    #[Route('/search/{sname}', name: 'app_supplier_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function search(SupplierRepository $supplierRepository, string $sname): JsonResponse
    {
        $suppliers = $supplierRepository->findsupplier($sname);
        if (!$suppliers) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }
        // Return the found suppliers
                $data = [];
        foreach ($suppliers as $supplier) {
            $data[] = [
                'id' => $supplier->getId(),
                'name' => $supplier->getName(),
                'email' => $supplier->getEmail(),
                'city' => $supplier->getCity(),
                'phone' => $supplier->getPhone(),
            ];
        }       
        return $this->json($data, Response::HTTP_OK);
}

    #[Route('/create', name: 'app_supplier_create', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

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
        // Log the creation of the supplier
        $this->logEntryService->createLogEntry('Supplier created: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/modify/{id}', name: 'app_supplier_update', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function update(Supplier $supplier, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $supplier->setName($data['name'] ?? $supplier->getName());
        $supplier->setEmail($data['email'] ?? $supplier->getEmail());
        $supplier->setCity($data['city'] ?? $supplier->getCity());
        $supplier->setPhone($data['phone'] ?? $supplier->getPhone());

        $em->flush();

        // Log the update of the supplier
        $this->logEntryService->createLogEntry('Supplier updated: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier updated successfully'], Response::HTTP_OK);
    
    }

    #[Route('/delete/{id}', name: 'app_supplier_delete', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function delete(Supplier $supplier, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($supplier);
        $em->flush();

        // Log the deletion of the supplier
        $this->logEntryService->createLogEntry('Supplier deleted: ' . $supplier->getName());
        return $this->json(['message' => 'Supplier deleted successfully'], Response::HTTP_NO_CONTENT);
    }

}
