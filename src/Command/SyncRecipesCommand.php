<?php

namespace App\Command;

use App\Service\RecipeSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sync-recipes')]
class SyncRecipesCommand extends Command
{
    public function __construct(private readonly RecipeSyncService $recipeSyncService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recipeSyncService->sync();
        $output->writeln('Synchronizacja zakończona!');

        return Command::SUCCESS;
    }
}
