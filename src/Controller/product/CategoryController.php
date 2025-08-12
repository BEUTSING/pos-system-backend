<?php

namespace App\Controller\product;

use App\Entity\Product\Category;
use App\Repository\Product\CategoryRepository;
use App\Service\LogEntryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CategoryController extends AbstractController
{
    private LogEntryService $logEntryService;
    public function __construct(private Security $security, LogEntryService $logEntryService)
    {
        $this->logEntryService = $logEntryService;
    }
// search category
      #[Route('/category/search/{cname}', name: 'app_category_search', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]
     public function search(CategoryRepository $repo, string $cname): JsonResponse
 {

        // Find categories by name
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

    #[Route('/category/list', name: 'app_category_display', methods: ['GET'])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

public function display(CategoryRepository $repo): JsonResponse
{
    $categorie=$repo->findAll();
    $data = [];
    foreach ($categorie as $category) {
        $data[] = [
            'id' => $category->getId(),
            'categoryname' => $category->getCategoryname(),
            'description' => $category->getDescription(),
        ];
    }
    // Return the data as JSON response 

    return $this->json($data, Response:: HTTP_OK);
}

    #[Route('/category/create', name: 'app_category_create',methods:["POST"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function create(Request $request,EntityManagerInterface $emi, Security $security): JsonResponse
    {
        $user = $security->getUser();

        $data=json_decode($request->getContent(),true);
         
        $categorie=new Category();
        $categorie->setCategoryname($data['categoryname']);
        $categorie->setDescription($data['description']);
       
        $emi->persist($categorie);
        $emi->flush();
        // Log the creation of the category
        $this->logEntryService->createLogEntry('Category created: ' . $categorie->getCategoryname());
    return $this->json($categorie, Response::HTTP_CREATED);
    }

    #[Route('/category/modify/{id}', name: 'app_category_update',methods:["PUT"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function update(Category $categorie, Request $request,EntityManagerInterface $emi): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
        
        $categorie->setCategoryname($data['categoryname']??$categorie->getCategoryname() );
        $categorie->setDescription($data['description']??$categorie->getDescription() );
        

        $emi->flush();
        // Log the update of the category
        $this->logEntryService->createLogEntry('Category updated: ' . $categorie->getCategoryname());
    return $this->json($categorie, Response::HTTP_OK);

    }
    
    #[Route('/category/delete/{id}', name: 'app_category_delete',methods:["DELETE"])]
    #[IsGranted(attribute: 'ROLE_MANAGER')]

    public function delete(Category $categorie,EntityManagerInterface $emi): JsonResponse
    {        
        $emi->remove($categorie);
        $emi->flush();
        // Log the deletion of the category
        $this->logEntryService->createLogEntry('Category deleted: ' . $categorie->getCategoryname());
           return $this->json(Null, 204);

    }
}
