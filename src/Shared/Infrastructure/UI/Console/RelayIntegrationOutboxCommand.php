<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Shared\Infrastructure\Integration\IntegrationSettings;
use App\Shared\Infrastructure\Integration\OutboxRelay;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'integration:outbox:relay',
    description: 'Publish the pending integration outbox messages to the broker',
)]
class RelayIntegrationOutboxCommand extends Command implements SignalableCommandInterface
{
    private const string LOCK_NAME = 'integration-outbox-relay';

    private bool $shouldStop = false;

    public function __construct(
        private readonly OutboxRelay $relay,
        private readonly IntegrationSettings $settings,
        private readonly LockFactory $lockFactory,
        private readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Maximum number of messages published per round', '100')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Milliseconds to wait between rounds without messages', '1000')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Seconds the relay keeps running before exiting', '3600')
            ->addOption('once', null, InputOption::VALUE_NONE, 'Publish a single batch and exit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batch = max(1, (int) $input->getOption('batch'));
        $sleep = max(1, (int) $input->getOption('sleep'));
        $deadline = time() + max(1, (int) $input->getOption('time-limit'));
        $once = (bool) $input->getOption('once');

        if (!$this->settings->enabled) {
            $io->note('Integration events are disabled.');

            if (!$once) {
                $this->sleepUntil($deadline, $sleep);
            }

            return Command::SUCCESS;
        }

        $lock = $this->lockFactory->createLock(self::LOCK_NAME);

        while (!$lock->acquire()) {
            $io->warning('Another relay is active.');

            if ($once || !$this->pause($deadline, $sleep)) {
                return Command::SUCCESS;
            }
        }

        try {
            do {
                $sent = $this->relay->relay($batch);
                $this->manager->clear();

                if ($sent > 0) {
                    $io->writeln(sprintf('%d integration message(s) published.', $sent), OutputInterface::VERBOSITY_VERBOSE);
                }

                if ($once) {
                    break;
                }

                if (0 === $sent && !$this->pause($deadline, $sleep)) {
                    break;
                }
            } while (!$this->shouldStop && time() < $deadline);
        } finally {
            $lock->release();
        }

        return Command::SUCCESS;
    }

    /** @return list<int> */
    public function getSubscribedSignals(): array
    {
        return \defined('SIGTERM') ? [\SIGTERM, \SIGINT] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;

        return false;
    }

    private function sleepUntil(int $deadline, int $sleep): void
    {
        do {
            $keepWaiting = $this->pause($deadline, $sleep);
        } while ($keepWaiting);
    }

    private function pause(int $deadline, int $sleep): bool
    {
        if ($this->shouldStop || time() >= $deadline) {
            return false;
        }

        usleep($sleep * 1000);

        return true;
    }
}
