<?php

namespace App\Controller\Admin;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/authors')]
class AuthorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthorRepository $authorRepository,
        private PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'admin_authors_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $query = $this->authorRepository->createQueryBuilder('a')
            ->select('a.id, a.name, COUNT(b.id) as bookCount')
            ->leftJoin('a.books', 'b')
            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC');

        $authors = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20 // 20 авторов на страницу
        );
        
        return $this->render('admin/authors/list.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/create', name: 'admin_authors_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $author = new Author();
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            
            if ($name) {
                $author->setName($name);
                $this->entityManager->persist($author);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Автор успешно создан');
                return $this->redirectToRoute('admin_authors_list');
            } else {
                $this->addFlash('error', 'Имя автора обязательно');
            }
        }
        
        return $this->render('admin/authors/create.html.twig', [
            'author' => $author,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_authors_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Author $author): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            
            if ($name) {
                $author->setName($name);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Автор успешно обновлен');
                return $this->redirectToRoute('admin_authors_list');
            } else {
                $this->addFlash('error', 'Имя автора обязательно');
            }
        }
        
        return $this->render('admin/authors/edit.html.twig', [
            'author' => $author,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_authors_delete', methods: ['POST'])]
    public function delete(Author $author): Response
    {
        $this->entityManager->remove($author);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Автор успешно удален');
        return $this->redirectToRoute('admin_authors_list');
    }
}
