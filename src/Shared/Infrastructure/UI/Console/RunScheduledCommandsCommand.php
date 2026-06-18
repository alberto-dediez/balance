<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Console;

use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandSchedule;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\ScheduledCommandRecord;
use App\Shared\Application\CommandBus\ScheduledCommandRecordRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Single entry point for periodic tasks. Meant to be triggered by cron (e.g. every 5 minutes);
 * it evaluates the hardcoded {@see CommandSchedule} and dispatches the commands that are due
 * based on their last recorded run time.
 */
#[AsCommand(
    name: 'app:schedule:run',
    description: 'Dispatch the scheduled commands that are due on the current tick',
)]
class RunScheduledCommandsCommand extends Command
{
    public function __construct(
        private readonly CommandSchedule $schedule,
        private readonly ScheduledCommandRecordRepository $records,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $schedulerLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the scheduled commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable('NOW');
        $commands = $this->schedule->commands();

        $this->schedulerLogger->debug('Scheduler tick', [
            'at' => $now->format('Y-m-d H:i:s \U\T\C'),
            'user' => $input->getArgument('username'),
            'total' => count($commands),
        ]);

        $dispatched = 0;

        foreach ($commands as $scheduledCommand) {
            $label = $this->commandLabel($scheduledCommand->command());
            $record = $this->records->findByCommandId($scheduledCommand->id());

            if (!$scheduledCommand->isDue($now, $record?->lastRunAt())) {
                $this->schedulerLogger->debug('Scheduled command skipped', [
                    'id' => $scheduledCommand->id(),
                    'command' => $label,
                    'schedule' => $scheduledCommand->describe(),
                    'lastRunAt' => $record?->lastRunAt()->format('Y-m-d H:i:s \U\T\C'),
                ]);

                continue;
            }

            CommandBusDispatcher::instance()->dispatch($scheduledCommand->command(), $user);

            if (null === $record) {
                $record = new ScheduledCommandRecord($scheduledCommand->id(), $now);
            } else {
                $record->update($now);
            }
            $this->records->save($record);

            ++$dispatched;

            $this->schedulerLogger->info('Scheduled command dispatched', [
                'id' => $scheduledCommand->id(),
                'command' => $label,
                'schedule' => $scheduledCommand->describe(),
                'at' => $now->format('Y-m-d H:i:s \U\T\C'),
            ]);
        }

        $this->schedulerLogger->info('Scheduler tick completed', [
            'dispatched' => $dispatched,
            'at' => $now->format('Y-m-d H:i:s \U\T\C'),
        ]);

        $io->success(sprintf('%d scheduled command(s) dispatched.', $dispatched));

        return Command::SUCCESS;
    }

    private function commandLabel(\App\Shared\Application\CommandBus\Command $command): string
    {
        if ($command instanceof DeferredCommand) {
            return sprintf('Deferred(%s)', (new \ReflectionClass($command->command()))->getShortName());
        }

        return (new \ReflectionClass($command))->getShortName();
    }
}
