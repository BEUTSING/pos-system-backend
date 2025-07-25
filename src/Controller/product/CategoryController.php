<?php

namespace App\Controller\product;

use App\Entity\Product\Category;
use App\Repository\Product\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{

// search category
      #[Route('/category/search/{cname}', name: 'app_category_search', methods: ['GET'])]
     public function search(CategoryRepository $repo, string $cname): JsonResponse
 {
     $categories = $repo->findCategory($cname);
        if (!$categories) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }
        // Return the found categories
        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'categoryname' => $category->getCategoryname(),
                'description' => $category->getDescription(),
            ];
        }
        // Return the data as JSON response
     return $this->json($data, Response::HTTP_OK);
     }

        #[Route('/category', name: 'app_category_display', methods: ['GET'])]
public function display(CategoryRepository $repo): JsonResponse
{

    return $this->json($repo->findAll(), Response:: HTTP_OK);
}

    #[Route('/category', name: 'app_category_create',methods:["POST"])]
    public function create(Request $request,EntityManagerInterface $emi, Security $security): JsonResponse
    {
        $user = $security->getUser();

        $data=json_decode($request->getContent(),true);
         
        $categorie=new Category();
        $categorie->setCategoryname($data['categoryname']);
        $categorie->setDescription($data['description']);

       
        $emi->persist($categorie);
        $emi->flush();
    return $this->json($categorie, Response::HTTP_CREATED);
    }

        #[Route('/category/{id}', name: 'app_category_update',methods:["PUT"])]
    public function update(Category $categorie, Request $request,EntityManagerInterface $emi): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
        
        $categorie->setCategoryname($data['categoryname']??$categorie->getCategoryname() );
        $categorie->setDescription($data['description']??$categorie->getDescription() );
        

        $emi->flush();
    return $this->json($categorie, Response::HTTP_OK);

    }
            #[Route('/category/{id}', name: 'app_category_delete',methods:["DELETE"])]
    public function delete(Category $categorie,EntityManagerInterface $emi): JsonResponse
    {        
        $emi->remove($categorie);
        $emi->flush();
           return $this->json(Null, 204);

    }
}
