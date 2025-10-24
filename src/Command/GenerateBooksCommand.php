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
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Создание случайного автора и книг');
        $io->info('Создаем случайного автора и 100,000 книг для него');

        // Создаем случайного автора
        $author = $this->createRandomAuthor($io);
        
        // Создаем книги для этого автора
        $this->createBooksForAuthor($author, $io);

        $io->success("Готово! Создан автор '{$author->getName()}' и 100,000 книг");
        return Command::SUCCESS;
    }

    private function createRandomAuthor(SymfonyStyle $io): Author
    {
        $firstNames = ['Александр', 'Михаил', 'Лев', 'Фёдор', 'Антон', 'Николай', 'Иван', 'Сергей', 'Владимир', 'Дмитрий'];
        $lastNames = ['Пушкин', 'Толстой', 'Достоевский', 'Чехов', 'Гоголь', 'Тургенев', 'Булгаков', 'Набоков', 'Солженицын', 'Пастернак'];
        
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $authorName = $firstName . ' ' . $lastName;
        
        $author = new Author();
        $author->setName($authorName);
        
        $this->entityManager->persist($author);
        $this->entityManager->flush();
        
        $io->success("Создан автор: {$authorName}");
        return $author;
    }

    private function createBooksForAuthor(Author $author, SymfonyStyle $io): void
    {
        $booksPerAuthor = 100000;
        $batchSize = 2000;

        $io->section('Создание книг...');
        
        $progressBar = $io->createProgressBar($booksPerAuthor);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %memory:6s% %message%');
        $progressBar->start();

        $titles = ['Книга', 'Роман', 'Повесть', 'Рассказ', 'Поэма', 'Стихи', 'Драма', 'Комедия', 'Трагедия', 'Сказка'];
        $descriptions = ['Описание книги', 'Интересная история', 'Классика литературы', 'Захватывающий сюжет', 'Философское произведение'];

        $batchCount = 0;

        // Создаем книги батчами
        for ($batch = 0; $batch < $booksPerAuthor; $batch += $batchSize) {
            $currentBatchSize = min($batchSize, $booksPerAuthor - $batch);
            
            // Загружаем автора ОДИН раз на батч
            $currentAuthor = $this->entityManager->find(Author::class, $author->getId());
            
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
            
            // Обновляем информацию о памяти в прогресс-баре
            $batchCount++;
            $memoryUsage = $this->formatBytes(memory_get_usage(true));
            $peakMemory = $this->formatBytes(memory_get_peak_usage(true));
            $progressBar->setMessage("Батч {$batchCount} | Пик: {$peakMemory}");
        }

        $progressBar->finish();
        $io->newLine(2);

    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
