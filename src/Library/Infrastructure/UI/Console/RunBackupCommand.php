<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Console;

use App\Library\Application\Media\RunBackup\RunMediaBackupRequest;
use App\Library\Application\Media\RunBackup\RunMediaBackupService;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:backup:run',
    description: 'Copy the library files to the backup storage',
)]
class RunBackupCommand extends Command
{
    public function __construct(private readonly RunMediaBackupService $backup)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $response = $this->backup->__invoke(
            new RunMediaBackupRequest(new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );

        $io->title('Copia de seguridad de la biblioteca');

        if (!$response->enabled) {
            $io->warning('La copia de seguridad está desactivada (MEDIA_BACKUP_ENABLED)');

            return Command::SUCCESS;
        }

        $io->table(
            ['Resultado', 'Archivos'],
            [
                ['copiados', (string) $response->copied],
                ['ya copiados', (string) $response->alreadyBackedUp],
                ['no encontrados en origen', (string) $response->missingInSource],
                ['purgados', (string) $response->purged],
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
