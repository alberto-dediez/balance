<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Console;

use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;
use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsService;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'data-exchange:purge-expired-exports',
    description: 'Remove expired exportation files and records',
)]
class PurgeExpiredExportationsCommand extends Command
{
    public function __construct(private readonly PurgeExpiredExportationsService $purgeExpiredExportations)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->purgeExpiredExportations->__invoke(
            new PurgeExpiredExportationsRequest(new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );

        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf('%d expired exportation(s) purged.', $response->purged));

        return Command::SUCCESS;
    }
}
