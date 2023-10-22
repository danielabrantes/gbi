<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Publisher;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ProjectController extends AbstractController
{
    /*
     *
     * List all books
     *
     */
    #[Route('/books', name: 'book_index', methods: ['get'])]
    public function index(ManagerRegistry $doctrine): JsonResponse
    {
        $products = $doctrine
            ->getRepository(Book::class)
            ->findAll();
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'author' => $product->getAuthorSerializedArray(),
                'publisher' => $product->getPublisherSerializedArray(),
            ];
        }
        return $this->json($data);
    }

    /*
     *
     * Create a book
     * Author       already created or throws 404 exception
     * Publisher    already created or throws 404 exception
     *
     */
    #[Route('/books', name: 'book_create', methods: ['post'])]
    public function create(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = new Book();
        $data = json_decode($request->getContent(), true);
        $project->setName($data['name']);
        $project->setDescription($data['description']);
        $project->setISBN($data['isbn']);
        foreach ($data['author'] as $author) {
            //safeguard if not exists
            $this->checkAuthorExists($doctrine, $author['id']);
            $project->addAuthor($this->getByClassAndId($doctrine, Author::class, $author['id']));
        }
        foreach ($data['publisher'] as $publisher) {
            //safeguard if not exists
            $this->checkPublisherExists($doctrine, $publisher['id']);
            $project->addPublisher($this->getByClassAndId($doctrine, Publisher::class, $publisher['id']));
        }
        $entityManager->persist($project);
        $entityManager->flush();
        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'isbn' => $project->getISBN(),
            'author' => $project->getAuthorSerializedArray(),
            'publisher' => $project->getPublisherSerializedArray(),
        ];
        return $this->json($data);
    }

    /*
     *
     * Get a book by id
     *
     */
    #[Route('/books/{id}', name: 'book_show', methods: ['get'])]
    public function show(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $project = $doctrine->getRepository(Book::class)->find($id);
        if (!$project) {
            return $this->json('No book found for id ' . $id, 404);
        }
        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'isbn' => $project->getISBN(),
            'author' => $project->getAuthorSerializedArray(),
            'publisher' => $project->getPublisherSerializedArray(),
        ];
        return $this->json($data);
    }

    /*
     *
     * Search book(s) by name
     *
     */
    #[Route('/books/search', name: 'book_search_post', methods: ['post'])]
    public function searchPost(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'];
        //dd($name);
        $projects = $doctrine->getRepository(Book::class)->findBy(['name' => $name]);
        //dd($projects);
        if (!$projects) {
            return $this->json('No book found with name ' . $name, 404);
        }
        foreach ($projects as $project) {
            $data[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'isbn' => $project->getISBN(),
                'author' => $project->getAuthorSerializedArray(),
                'publisher' => $project->getPublisherSerializedArray(),
            ];
        }
        return $this->json($data);
    }

    /*
     *
     * Change book get metadata by book id
     *
     */
    #[Route('/books/{id}', name: 'book_update', methods: ['patch'])]
    public function update(ManagerRegistry $doctrine, Request $request, int $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = $entityManager->getRepository(Book::class)->find($id);
        if (!$project) {
            return $this->json('No book found for id' . $id, 404);
        }
        $data = json_decode($request->getContent(), true);
        $project->setName($data['name']);
        $project->setDescription($data['description']);
        $project->setISBN($data['isbn']);
        $project->clearAuthors();
        $project->clearPublisher();
        $entityManager->persist($project);
        foreach ($data['author'] as $author) {
            //safeguard if not exists
            $this->checkAuthorExists($doctrine, $author['id']);
            $project->addAuthor($this->getByClassAndId($doctrine, Author::class, $author['id']));
        }
        foreach ($data['publisher'] as $publisher) {
            //safeguard if not exists
            $this->checkPublisherExists($doctrine, $publisher['id']);
            $project->addPublisher($this->getByClassAndId($doctrine, Publisher::class, $publisher['id']));
        }
        $entityManager->persist($project);
        $entityManager->flush();
        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'isbn' => $project->getISBN(),
            'author' => $project->getAuthorSerializedArray(),
            'publisher' => $project->getPublisherSerializedArray(),
        ];
        return $this->json($data);
    }

    /*
     *
     * Delete a book by id
     *
     */
    #[Route('/books/{id}', name: 'book_delete', methods: ['delete'])]
    public function delete(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = $entityManager->getRepository(Book::class)->find($id);
        if (!$project) {
            return $this->json('No book found for id' . $id, 404);
        }
        $entityManager->remove($project);
        $entityManager->flush();
        return $this->json('Deleted a book successfully with id ' . $id);
    }

    private function checkAuthorExists(ManagerRegistry $doctrine, int $id)
    {
        $exists = $this->getByClassAndId($doctrine, Author::class, $id);
        if (!$exists) {
            throw new NotFoundHttpException('Sorry author do not exist!');
        }
    }

    private function getByClassAndId(ManagerRegistry $doctrine, $class, int $id)
    {
        $exists = $doctrine->getRepository($class)->findOneBy(['id' => $id]);
        return $exists;
    }

    private function checkPublisherExists(ManagerRegistry $doctrine, int $id)
    {
        $exists = $this->getByClassAndId($doctrine, Publisher::class, $id);
        if (!$exists) {
            throw new NotFoundHttpException('Sorry publisher do not exist!');
        }
    }
}