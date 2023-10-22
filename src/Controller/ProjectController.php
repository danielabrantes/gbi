<?php

namespace App\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\Routing\Annotation\Route;

//class ProjectController extends AbstractController
//{
//    #[Route('/project', name: 'app_project')]
//    public function index(): JsonResponse
//    {
//        return $this->json([
//            'message' => 'Welcome to your new controller!',
//            'path' => 'src/Controller/ProjectController.php',
//        ]);
//    }
//}


//namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Book;

#[Route('/api', name: 'api_')]
class ProjectController extends AbstractController
{



    #[Route('/books', name: 'book_create', methods: ['post'])]
    public function create(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();

        $project = new Book();
        $project->setName($request->request->get('name'));
        $project->setDescription($request->request->get('description'));

        $entityManager->persist($project);
        $entityManager->flush();

        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
        ];

        return $this->json($data);
    }



    #[Route('/books/{id}', name: 'book_update', methods: ['put', 'patch'])]
    public function update(ManagerRegistry $doctrine, Request $request, int $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = $entityManager->getRepository(Book::class)->find($id);

        if (!$project) {
            return $this->json('No book found for id' . $id, 404);
        }

        $project->setName($request->request->get('name'));
        $project->setDescription($request->request->get('description'));
        $entityManager->flush();

        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
        ];

        return $this->json($data);
    }


}