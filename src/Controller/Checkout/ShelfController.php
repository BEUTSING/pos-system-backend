<?php

namespace App\Controller\Checkout;

use App\Repository\Checkout\ShelfRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShelfController extends AbstractController
{    public function display(ShelfRepository $shelfrepository): JsonResponse
    {
       return $this->json($shelfrepository->findAll(), Response::HTTP_OK);
    }


}