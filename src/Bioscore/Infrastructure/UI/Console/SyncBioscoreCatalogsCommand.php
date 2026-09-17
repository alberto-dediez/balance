<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Console;

use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'bioscore:catalogs:sync',
    description: 'Synchronize Bioscore catalogs and map them to the platform masters',
)]
class SyncBioscoreCatalogsCommand extends Command
{
    public function __construct(
        private readonly SyncBioscoreCatalogsService $syncCatalogs,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the synchronization')
            ->addOption('catalog', null, InputOption::VALUE_REQUIRED, 'Synchronize only this catalog')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $catalog = $input->getOption('catalog');

        if (null !== $catalog && !is_string($catalog)) {
            $io->error('The catalog option must be a string.');

            return Command::FAILURE;
        }

        $bioscoreCatalog = null !== $catalog ? BioscoreCatalog::tryFrom($catalog) : null;

        if (null !== $catalog && null === $bioscoreCatalog) {
            $io->error(sprintf('Unknown catalog "%s".', $catalog));

            return Command::FAILURE;
        }

        $response = $this->syncCatalogs->__invoke(
            new SyncBioscoreCatalogsRequest(catalog: $bioscoreCatalog, now: new \DateTimeImmutable('NOW')),
            $user,
        );

        $io->table(['Counter', 'Value'], [
            ['Registered', $response->registered],
            ['Updated', $response->updated],
            ['Auto-mapped', $response->autoMapped],
            ['Withdrawn', $response->withdrawn],
            ['Restored', $response->restored],
        ]);

        $this->listItems($io, 'New unmapped items', $response->newPending);
        $this->listItems($io, 'Invalidated mappings', $response->invalidated);

        if ([] !== $response->failedCatalogs) {
            $io->warning(sprintf(
                'Catalogs that could not be downloaded: %s',
                implode(', ', array_map(static fn(BioscoreCatalog $failed): string => $failed->value, $response->failedCatalogs)),
            ));
        }

        return Command::SUCCESS;
    }

    /** @param list<BioscoreCatalogItem> $items */
    private function listItems(SymfonyStyle $io, string $title, array $items): void
    {
        if ([] === $items) {
            return;
        }

        $io->section($title);
        $io->listing(array_map(
            static fn(BioscoreCatalogItem $item): string => sprintf('%s · #%d · %s', $item->catalog()->value, $item->bioscoreId(), $item->name()),
            $items,
        ));
    }
}
