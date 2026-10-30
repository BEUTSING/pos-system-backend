<?php

namespace App\Controller\Billing;

use App\Service\Billing\InvoiceService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Billing')]
#[Route('/invoice')]
final class InvoiceController extends AbstractController
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    #[Route('/list', name: 'app_invoice_list', methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    #[OA\Get(path: '/api/v1/invoice/list', summary: 'List invoices of the active company')]
    public function list(): JsonResponse
    {
        try {
            return $this->json($this->invoiceService->listInvoices());
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'app_invoice_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    #[OA\Get(path: '/api/v1/invoice/{id}', summary: 'Get an invoice by ID')]
    public function show(int $id): JsonResponse
    {
        try {
            return $this->json($this->invoiceService->getInvoice($id));
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('', name: 'app_invoice_create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Post(path: '/api/v1/invoice', summary: 'Create a manual invoice')]
    public function create(Request $request): JsonResponse
    {
        try {
            return $this->json($this->invoiceService->createInvoice($request), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/from-sale/{saleId}', name: 'app_invoice_from_sale', requirements: ['saleId' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Post(path: '/api/v1/invoice/from-sale/{saleId}', summary: 'Generate an invoice from a sale')]
    public function createFromSale(int $saleId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $taxRate = is_array($data) && isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0.0;

        try {
            return $this->json($this->invoiceService->createFromSale($saleId, $taxRate), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'app_invoice_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Put(path: '/api/v1/invoice/{id}', summary: 'Update an invoice status, tax rate, notes or due date')]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            return $this->json($this->invoiceService->updateInvoice($id, $request));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'app_invoice_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(path: '/api/v1/invoice/{id}', summary: 'Delete an invoice')]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->invoiceService->deleteInvoice($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
