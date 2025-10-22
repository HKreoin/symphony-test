<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-books',
    description: 'Создать 3 авторов и 100 тысяч книг для каждого',
)]
class GenerateBooksCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '3072M');

        $io = new SymfonyStyle($input, $output);
        
        $io->title('Создание тестовых данных');
        $io->info('Создаем 3 авторов и по 100,000 книг для каждого');

        $io->section('1. Очистка старых данных...');
        $this->clearData();
        $io->success('Старые данные удалены');

        $io->section('2. Создание авторов...');
        $authors = $this->createAuthors();
        $io->success('Авторы созданы: ' . implode(', ', array_map(fn($a) => $a->getName(), $authors)));

        $io->section('3. Создание книг...');
        $this->createBooks($authors, $io);

        $io->success('Готово! Создано 3 автора и 300,000 книг');
        return Command::SUCCESS;
    }

    private function clearData(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Book')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Author')->execute();
        $this->entityManager->flush();
    }

    private function createAuthors(): array
    {
        $names = [
            'Лев Толстой',
            'Фёдор Достоевский', 
            'Антон Чехов'
        ];

        $authors = [];
        foreach ($names as $name) {
            $author = new Author();
            $author->setName($name);
            $this->entityManager->persist($author); // Готовим к сохранению
            $authors[] = $author;
        }

        $this->entityManager->flush(); // Сохраняем в базу
        return $authors;
    }

    private function createBooks(array $authors, SymfonyStyle $io): void
    {
        $booksPerAuthor = 100000;
        $batchSize = 10000;

        $totalBooks = count($authors) * $booksPerAuthor;
        $progressBar = $io->createProgressBar($totalBooks);
        $progressBar->start();

        $titles = ['Книга', 'Роман', 'Повесть', 'Рассказ', 'Поэма'];
        $descriptions = ['Описание книги', 'Интересная история', 'Классика литературы'];

        foreach ($authors as $author) {
            $authorId = $author->getId();
            
            // Создаем книги батчами для каждого автора
            for ($batch = 0; $batch < $booksPerAuthor; $batch += $batchSize) {
                $currentBatchSize = min($batchSize, $booksPerAuthor - $batch);
                
                // Загружаем автора ОДИН раз на батч
                $currentAuthor = $this->entityManager->find(Author::class, $authorId);
                
                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $book = new Book();
                    $book->setTitle($titles[array_rand($titles)] . ' ' . ($batch + $i + 1));
                    $book->setDescription($descriptions[array_rand($descriptions)]);
                    $book->setAuthor($currentAuthor);
                    
                    $this->entityManager->persist($book);
                    $progressBar->advance();
                }
                
                // Сохраняем батч и очищаем память
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $progressBar->finish();
        $io->newLine(2);
    }
}
