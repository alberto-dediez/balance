<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Domain\Model\Integration\IntegrationInboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationInboxStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'integration:inbox:list',
    description: 'List the integration inbox messages',
)]
class ListIntegrationInboxCommand extends Command
{
    public function __construct(private readonly IntegrationInboxRepository $inbox)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'processed, skipped_stale, skipped_unknown_type or skipped_hops')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source platform')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Integration event type')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of messages', '20')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $statusOption = $input->getOption('status');
        $status = null;

        if (is_string($statusOption) && '' !== $statusOption) {
            $status = IntegrationInboxStatus::tryFrom($statusOption);

            if (!$status instanceof IntegrationInboxStatus) {
                $io->error(sprintf('Unknown status "%s".', $statusOption));

                return Command::FAILURE;
            }
        }

        $source = $input->getOption('source');
        $type = $input->getOption('type');

        $messages = $this->inbox->list(new IntegrationInboxFilter(
            status: $status,
            source: is_string($source) ? $source : null,
            type: is_string($type) ? $type : null,
            limit: (int) $input->getOption('limit'),
        ));

        $rows = [];

        foreach ($messages as $message) {
            $rows[] = [
                $message->messageId(),
                $message->source(),
                $message->type(),
                $message->version(),
                $message->sequence(),
                $message->aggregateId(),
                $message->status()->value,
                $message->correlationId(),
                $message->hops(),
                $message->receivedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        $io->table(
            ['messageId', 'source', 'type', 'version', 'sequence', 'aggregateId', 'status', 'correlationId', 'hops', 'receivedAt'],
            $rows,
        );

        return Command::SUCCESS;
    }
}
