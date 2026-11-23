<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationOutboxStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'integration:outbox:list',
    description: 'List the integration outbox messages',
)]
class ListIntegrationOutboxCommand extends Command
{
    public function __construct(private readonly IntegrationOutboxRepository $outbox)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'pending, sent or all', IntegrationOutboxStatus::All->value)
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Integration event type')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of messages', '20')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $status = IntegrationOutboxStatus::tryFrom((string) $input->getOption('status'));

        if (!$status instanceof IntegrationOutboxStatus) {
            $io->error(sprintf('Unknown status "%s".', $input->getOption('status')));

            return Command::FAILURE;
        }

        $type = $input->getOption('type');

        $messages = $this->outbox->list(new IntegrationOutboxFilter(
            status: $status,
            type: is_string($type) ? $type : null,
            limit: (int) $input->getOption('limit'),
        ));

        $rows = [];

        foreach ($messages as $message) {
            $rows[] = [
                $message->sequence(),
                $message->type(),
                $message->version(),
                $message->aggregateId(),
                $message->correlationId(),
                $message->hops(),
                $message->occurredOn()->format(\DateTimeInterface::ATOM),
                $message->sentAt()?->format(\DateTimeInterface::ATOM),
                $message->attempts(),
                $message->lastError(),
            ];
        }

        $io->table(
            ['sequence', 'type', 'version', 'aggregateId', 'correlationId', 'hops', 'occurredOn', 'sentAt', 'attempts', 'lastError'],
            $rows,
        );

        return Command::SUCCESS;
    }
}
