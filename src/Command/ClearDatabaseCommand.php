<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-database',
    description: 'Очистить базу данных от всех данных',
)]
class ClearDatabaseCommand extends Command
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
        
        $io->title('Очистка базы данных');
        
        // Очищаем книги
        $io->section('Удаление книг...');
        $booksCount = $this->entityManager->createQuery('DELETE FROM App\Entity\Book')->execute();
        $io->success("Удалено книг: {$booksCount}");
        
        // Очищаем авторов
        $io->section('Удаление авторов...');
        $authorsCount = $this->entityManager->createQuery('DELETE FROM App\Entity\Author')->execute();
        $io->success("Удалено авторов: {$authorsCount}");
        
        $io->success('База данных очищена!');
        return Command::SUCCESS;
    }
}
