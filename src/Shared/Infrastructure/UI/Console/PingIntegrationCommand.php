<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Application\Integration\IntegrationEventPublisher;
use App\Shared\Application\Integration\Ping\PlatformPinged;
use App\Shared\Infrastructure\Integration\IntegrationSettings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'integration:ping',
    description: 'Publish a platform.pinged integration event',
)]
class PingIntegrationCommand extends Command
{
    public function __construct(
        private readonly IntegrationEventPublisher $publisher,
        private readonly IntegrationSettings $settings,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::OPTIONAL, 'Message carried by the ping', 'ping');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->settings->enabled) {
            $io->warning('Integration events are disabled (INTEGRATION_EVENTS_ENABLED=false).');

            return Command::FAILURE;
        }

        $this->publisher->publish(new PlatformPinged((string) $input->getArgument('message')));

        $io->success(sprintf('platform.pinged published by "%s".', $this->settings->platform));

        return Command::SUCCESS;
    }
}
