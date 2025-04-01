<?php

namespace App\Command;

use App\Service\RecipeSyncService;
use App\Service\ServiceException\RecipeSyncException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:sync-recipes',
    description: 'Fetches recipes from an external API and stores them locally.',
)]
class SyncRecipesCommand extends Command
{
    public function __construct(
        private readonly RecipeSyncService $recipeSyncService,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info($this->translator->trans('sync.start', [], 'commands'));
            $result = $this->recipeSyncService->sync();

            $io->success(sprintf(
                $this->translator->trans('sync.success', ['%count%' => $result['added'] ?? 0], 'commands'), // Example detail
                $result['added'] ?? 0
            ));

            return Command::SUCCESS;
        } catch (RecipeSyncException $e) {
            $this->logger->error(
                $this->translator->trans('sync.error.api', ['%message%' => $e->getMessage()], 'commands'),
                ['exception' => $e]
            );
            $io->error($this->translator->trans('sync.error.api', ['%message%' => $e->getMessage()], 'commands'));

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->logger->critical(
                $this->translator->trans('sync.error.unexpected', ['%message%' => $e->getMessage()], 'commands'),
                ['exception' => $e]
            );
            $io->error($this->translator->trans('sync.error.unexpected', ['%message%' => $e->getMessage()], 'commands'));

            return Command::FAILURE;
        }
    }
}
