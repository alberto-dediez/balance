<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Console;

use App\Library\Application\Media\ExtractBackup\ExtractMediaBackupRequest;
use App\Library\Application\Media\ExtractBackup\ExtractMediaBackupService;
use App\Library\Application\Media\RestoreBackup\RestoreMediaBackupRequest;
use App\Library\Application\Media\RestoreBackup\RestoreMediaBackupService;
use App\Library\Domain\Model\Backup\MediaBackupNotFoundException;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:backup:restore',
    description: 'Restore the library files from the backup storage',
)]
class RestoreBackupCommand extends Command
{
    public function __construct(
        private readonly RestoreMediaBackupService $restore,
        private readonly ExtractMediaBackupService $extract,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('media', null, InputOption::VALUE_REQUIRED, 'Only restore the file of this media')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Extract the backup of the media to this local file instead of restoring it')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be restored without writing anything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mediaId = $input->getOption('media');
        $outputPath = $input->getOption('output');

        if (null !== $outputPath && null === $mediaId) {
            $io->error('The --output option requires --media');

            return Command::INVALID;
        }

        if (null !== $outputPath) {
            return $this->extract($io, (string) $mediaId, (string) $outputPath);
        }

        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $response = $this->restore->__invoke(
                new RestoreMediaBackupRequest(
                    mediaId: null === $mediaId ? null : new MediaId((string) $mediaId),
                    dryRun: $dryRun,
                ),
                UserMother::systemUser(),
            );
        } catch (MediaNotFoundException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->title('Restauración de la biblioteca desde la copia de seguridad');

        if ($dryRun) {
            $io->note('Dry run: no se ha escrito nada');
        }

        $io->table(
            ['Resultado', 'Archivos'],
            [
                ['restaurados', (string) $response->restored],
                ['ya en el almacén principal', (string) $response->alreadyInPrimary],
                ['no encontrados en la copia', (string) $response->missingInBackup],
                ['errores', (string) count($response->failures)],
            ],
        );

        if ([] !== $response->failures) {
            $io->listing($response->failures);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function extract(SymfonyStyle $io, string $mediaId, string $outputPath): int
    {
        try {
            $response = $this->extract->__invoke(
                new ExtractMediaBackupRequest($mediaId, $outputPath),
                UserMother::systemUser(),
            );
        } catch (MediaBackupNotFoundException|\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Archivo extraído en %s', $response->outputPath));

        return Command::SUCCESS;
    }
}
