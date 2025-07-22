<?php

namespace App\Controller\SecurityController;

use App\Entity\Product\Category;
use App\Repository\Product\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{

// code de recherhce de categorie
      #[Route('/api/category/search/{cname}', name: 'app_search', methods: ['GET'])]
     public function search(CategoryRepository $repo, string $cname): JsonResponse
 {
     $categories = $repo->findCategory($cname);
     return $this->json($categories, Response::HTTP_OK);
     }

        #[Route('/api/category', name: 'app_display', methods: ['GET'])]
public function display(CategoryRepository $repo): JsonResponse
{

    return $this->json($repo->findAll(), Response:: HTTP_OK);
}

    #[Route('/api/category', name: 'app_create',methods:["POST"])]
    public function create(Request $request,EntityManagerInterface $emi): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
         
        $categorie=new Category();
        $categorie->setCategoryname($data['categoryname']);
        $categorie->setDescription($data['description']);
       
        $emi->persist($categorie);
        $emi->flush();
    return $this->json($categorie, Response::HTTP_CREATED);
    }

        #[Route('/api/category/{id}', name: 'app_update',methods:["PUT"])]
    public function update(Category $categorie, Request $request,EntityManagerInterface $emi): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
        
        $categorie->setCategoryname($data['categoryname']??$categorie->getCategoryname() );
         $categorie->setDescription($data['description']??$categorie->getDescription() );
        

        $emi->flush();
    return $this->json($categorie, Response::HTTP_OK);

    }
            #[Route('/api/category/{id}', name: 'app_delete',methods:["DELETE"])]
    public function delete(Category $categorie,EntityManagerInterface $emi): JsonResponse
    {        
        $emi->remove($categorie);
        $emi->flush();
           return $this->json(Null, 204);

    }
}
