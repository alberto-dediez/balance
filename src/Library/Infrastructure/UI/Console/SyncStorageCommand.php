<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Console;

use App\Library\Application\Media\SyncStorage\SyncMediaStorageRequest;
use App\Library\Application\Media\SyncStorage\SyncMediaStorageService;
use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Domain\Model\StorageSync\StorageSyncTaskRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:storage:sync',
    description: 'Upload to the main storage the library files kept in the local fallback',
)]
class SyncStorageCommand extends Command
{
    public function __construct(
        private readonly SyncMediaStorageService $sync,
        private readonly StorageSyncTaskRepository $tasks,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('list', null, InputOption::VALUE_NONE, 'Only show the files pending to be uploaded')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $failed = 0;

        if (!$input->getOption('list')) {
            $response = $this->sync->__invoke(
                new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')),
                UserMother::systemUser(),
            );

            $failed = $response->failed;

            $io->title('Sincronización del almacenamiento de la biblioteca');

            $io->table(
                ['Resultado', 'Archivos'],
                [
                    ['subidos', (string) $response->uploaded],
                    ['descartados', (string) $response->dropped],
                    ['fallidos', (string) $response->failed],
                    ['pendientes', (string) $response->pending],
                ],
            );
        }

        $pending = $this->tasks->findPending(0);

        if ([] === $pending) {
            $io->success('No hay archivos pendientes');
        } else {
            $io->table(
                ['Ruta', 'Intentos', 'Último intento', 'Último error'],
                array_map(
                    static fn(StorageSyncTask $task): array => [
                        $task->path(),
                        (string) $task->attempts(),
                        $task->lastAttemptAt()?->format('d/m/Y H:i:s') ?? '',
                        $task->lastError() ?? '',
                    ],
                    $pending,
                ),
            );
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
