<?php

namespace App\Controller\Checkout;

use App\Service\Checkout\CustomerService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Customers')]
final class CustomerController extends AbstractController
{
    public function __construct(private CustomerService $customerService)
    {
    }

    #[Route('/customer/create', name: 'app_customer_create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Post(path: '/api/v1/customer/create', summary: 'Create a customer')]
    public function create(Request $request): JsonResponse
    {
        try {
            return $this->json($this->customerService->createCustomer($request), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/list', name: 'app_customer_list', methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    #[OA\Get(path: '/api/v1/customer/list', summary: 'List customers for the active company')]
    public function list(): JsonResponse
    {
        try {
            return $this->json($this->customerService->listCustomers());
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/search', name: 'app_customer_search', methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    #[OA\Get(path: '/api/v1/customer/search', summary: 'Search customers by name, phone, or email')]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        if (!is_string($query) || trim($query) === '') {
            return $this->json(['error' => 'The query parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($this->customerService->searchCustomers($query));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/{id}', name: 'app_customer_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    #[OA\Get(path: '/api/v1/customer/{id}', summary: 'Get a customer by ID')]
    public function show(int $id): JsonResponse
    {
        try {
            return $this->json($this->customerService->getCustomer($id));
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/modify/{id}', name: 'app_customer_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Put(path: '/api/v1/customer/modify/{id}', summary: 'Update a customer')]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            return $this->json($this->customerService->updateCustomer($id, $request));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/delete/{id}', name: 'app_customer_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Delete(path: '/api/v1/customer/delete/{id}', summary: 'Delete a customer')]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->customerService->deleteCustomer($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
