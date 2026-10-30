<?php

namespace App\Controller\Media;

use App\Service\Media\LogoService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Media')]
final class LogoController extends AbstractController
{
    public function __construct(private LogoService $logoService)
    {
    }

    // ─── COMPANY LOGO ───────────────────────────────────────────────────────────

    #[Route('/company/logo', name: 'app_company_logo_list', methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    public function listCompanyLogos(): JsonResponse
    {
        try {
            return $this->json($this->logoService->listCompanyLogos());
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/company/logo', name: 'app_company_logo_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addCompanyLogo(Request $request): JsonResponse
    {
        try {
            return $this->json($this->logoService->addCompanyLogo($request), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/company/logo', name: 'app_company_logo_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCompanyLogo(): JsonResponse
    {
        try {
            $this->logoService->deleteCompanyLogo();
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/company/logo/{id}', name: 'app_company_logo_delete_one', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCompanyLogoById(int $id): JsonResponse
    {
        try {
            $this->logoService->deleteCompanyLogo($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    // ─── CUSTOMER PICTURE ───────────────────────────────────────────────────────

    #[Route('/customer/{id}/image', name: 'app_customer_image_list', requirements: ['id' => '\\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_WAITER')]
    public function listCustomerImages(int $id): JsonResponse
    {
        try {
            return $this->json($this->logoService->listCustomerLogos($id));
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/{id}/image', name: 'app_customer_image_add', requirements: ['id' => '\\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function addCustomerImage(int $id, Request $request): JsonResponse
    {
        try {
            return $this->json($this->logoService->addCustomerLogo($id, $request), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/{id}/image', name: 'app_customer_image_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_MANAGER')]
    public function deleteCustomerImage(int $id): JsonResponse
    {
        try {
            $this->logoService->deleteCustomerImage($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/customer/{id}/image/{logoId}', name: 'app_customer_image_delete_one', requirements: ['id' => '\\d+', 'logoId' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_MANAGER')]
    public function deleteCustomerImageById(int $id, int $logoId): JsonResponse
    {
        try {
            $this->logoService->deleteCustomerImage($id, $logoId);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
