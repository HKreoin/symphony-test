<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class BookApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $bookRepository,
        private AuthorRepository $authorRepository
    ) {}

    #[Route('/books/list', name: 'api_books_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $books = $this->bookRepository->findAllWithAuthor();
        
        return new JsonResponse([
            'success' => true,
            'data' => $books,
            'count' => count($books)
        ]);
    }

    #[Route('/books/by-id', name: 'api_books_by_id', methods: ['GET'])]
    public function getById(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $id = $request->query->get('id');
        
        if (!$id) {
            return new JsonResponse(['error' => 'ID parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $book = $this->bookRepository->find($id);
        
        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'description' => $book->getDescription(),
                'authorName' => $book->getAuthor()?->getName()
            ]
        ]);
    }

    #[Route('/books/update', name: 'api_books_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['id'])) {
            return new JsonResponse(['error' => 'ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $book = $this->bookRepository->find($data['id']);
        
        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        if (isset($data['title'])) {
            $book->setTitle($data['title']);
        }
        
        if (isset($data['description'])) {
            $book->setDescription($data['description']);
        }
        
        if (isset($data['author_id'])) {
            $author = $this->authorRepository->find($data['author_id']);
            if ($author) {
                $book->setAuthor($author);
            } else {
                return new JsonResponse(['error' => 'Author not found'], Response::HTTP_BAD_REQUEST);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'description' => $book->getDescription(),
                'authorName' => $book->getAuthor()?->getName()
            ]
        ]);
    }

    #[Route('/books/{id}', name: 'api_books_delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        if (!$this->checkApiKey($request)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $book = $this->bookRepository->find($id);
        
        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($book);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Book deleted successfully'
        ]);
    }

    private function checkApiKey(Request $request): bool
    {
        return $request->headers->get('X-API-User-Name') === 'admin';
    }
}
