<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/books')]
class BookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $bookRepository,
        private AuthorRepository $authorRepository,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'admin_books_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $query = $this->bookRepository->createQueryBuilder('b')
            ->select('b.id, b.title, b.description, a.name as authorName')
            ->leftJoin('b.author', 'a')
            ->orderBy('b.title', 'ASC');

        $books = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            50 // 50 книг на страницу
        );
        
        return $this->render('admin/books/list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/create', name: 'admin_books_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $book = new Book();
        $authors = $this->authorRepository->findAll();
        
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $authorId = $request->request->get('author_id');
            
            if ($title && $authorId) {
                $author = $this->authorRepository->find($authorId);
                if ($author) {
                    $book->setTitle($title);
                    $book->setDescription($description);
                    $book->setAuthor($author);
                    
                    $this->entityManager->persist($book);
                    $this->entityManager->flush();
                    
                    $this->addFlash('success', 'Книга успешно создана');
                    return $this->redirectToRoute('admin_books_list');
                } else {
                    $this->addFlash('error', 'Автор не найден');
                }
            } else {
                $this->addFlash('error', 'Название книги и автор обязательны');
            }
        }
        
        return $this->render('admin/books/create.html.twig', [
            'book' => $book,
            'authors' => $authors,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_books_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book): Response
    {
        $authors = $this->authorRepository->findAll();
        
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $authorId = $request->request->get('author_id');
            
            if ($title && $authorId) {
                $author = $this->authorRepository->find($authorId);
                if ($author) {
                    $book->setTitle($title);
                    $book->setDescription($description);
                    $book->setAuthor($author);
                    
                    $this->entityManager->flush();
                    
                    $this->addFlash('success', 'Книга успешно обновлена');
                    return $this->redirectToRoute('admin_books_list');
                } else {
                    $this->addFlash('error', 'Автор не найден');
                }
            } else {
                $this->addFlash('error', 'Название книги и автор обязательны');
            }
        }
        
        return $this->render('admin/books/edit.html.twig', [
            'book' => $book,
            'authors' => $authors,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_books_delete', methods: ['POST'])]
    public function delete(Book $book): Response
    {
        $this->entityManager->remove($book);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Книга успешно удалена');
        return $this->redirectToRoute('admin_books_list');
    }
}
