<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Console;

use App\Library\Application\Media\MigrateStorage\MigrateMediaStorageRequest;
use App\Library\Application\Media\MigrateStorage\MigrateMediaStorageService;
use App\Library\Infrastructure\Storage\MediaStorageRegistry;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:storage:migrate',
    description: 'Copy library files from one storage to another',
)]
class MigrateStorageCommand extends Command
{
    public function __construct(
        private readonly MigrateMediaStorageService $migrate,
        private readonly MediaStorageRegistry $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'The storage to copy the files from', 'local')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'The storage to copy the files to', 'ovh')
            ->addOption('delete-source', null, InputOption::VALUE_NONE, 'Delete each file from the source once it is in the target')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be migrated without writing or deleting anything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $from = (string) $input->getOption('from');
        $to = (string) $input->getOption('to');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($from === $to) {
            $io->error('Source and target storages must differ');

            return Command::INVALID;
        }

        try {
            $source = $this->registry->get($from);
            $target = $this->registry->get($to);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        $response = $this->migrate->__invoke(
            new MigrateMediaStorageRequest(
                source: $source,
                target: $target,
                deleteSource: (bool) $input->getOption('delete-source'),
                dryRun: $dryRun,
            ),
            UserMother::systemUser(),
        );

        $io->title('Migración del almacenamiento de la biblioteca');

        if ($dryRun) {
            $io->note('Dry run: no se ha escrito ni borrado nada');
        }

        $io->table(
            ['Resultado', 'Archivos'],
            [
                ['migrados', (string) $response->migrated],
                ['ya en destino', (string) $response->alreadyInTarget],
                ['no encontrados en origen', (string) $response->missingInSource],
                ['borrados del origen', (string) $response->deletedFromSource],
                ['errores', (string) count($response->failures)],
            ],
        );

        if ($response->missingInSource > 0) {
            $io->warning(sprintf('%d archivo(s) no encontrados en origen', $response->missingInSource));
        }

        if ([] !== $response->failures) {
            $io->listing($response->failures);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
