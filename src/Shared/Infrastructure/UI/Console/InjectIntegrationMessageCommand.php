<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Infrastructure\Integration\Messenger\IncomingIntegrationMessage;
use App\Shared\Infrastructure\Integration\Messenger\IntegrationMessageSerializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

#[AsCommand(
    name: 'integration:inject',
    description: 'Process an integration envelope read from a JSON file as if it came from the broker',
)]
class InjectIntegrationMessageCommand extends Command
{
    public function __construct(
        #[Autowire(service: IntegrationMessageSerializer::class)]
        private readonly SerializerInterface $serializer,
        private readonly MessageBusInterface $bus,
        private readonly IntegrationInboxRepository $inbox,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path of the JSON file with the integration envelope');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');

        if (!is_file($file) || !is_readable($file)) {
            $io->error(sprintf('File "%s" cannot be read.', $file));

            return Command::FAILURE;
        }

        $envelope = $this->serializer->decode(['body' => (string) file_get_contents($file), 'headers' => []]);

        try {
            $this->bus->dispatch($envelope->with(new TransportNamesStamp(['sync'])));
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $message = $envelope->getMessage();

        if (!$message instanceof IncomingIntegrationMessage) {
            return Command::FAILURE;
        }

        $integrationEnvelope = $message->envelope;

        $inboxMessage = $this->inbox->find($integrationEnvelope->id);

        if (null !== $inboxMessage) {
            $io->success(sprintf('Message "%s" recorded in the inbox as "%s".', $integrationEnvelope->id, $inboxMessage->status()->value));

            return Command::SUCCESS;
        }

        $io->note(sprintf('Message "%s" was not recorded in the inbox.', $integrationEnvelope->id));

        return Command::SUCCESS;
    }
}
