<?php

declare(strict_types=1);

namespace App\Shared\Application\JobProgress;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\TrackedCommand;
use App\Shared\Domain\Model\JobProgress\JobProgress;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressRepository;
use App\Shared\Domain\Model\JobProgress\JobProgressTracker;

class TrackedCommandHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(private readonly JobProgressRepository $jobProgressRepository) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof TrackedCommand;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof TrackedCommand);

        $key = JobProgressKey::fromString($command->progressKey());
        $jobProgress = $this->jobProgressRepository->findByKey($key);

        if (null !== $jobProgress) {
            $jobProgress->reset();
        } else {
            $jobProgress = JobProgress::start(
                key: $key,
                total: $command->progressTotal(),
            );
        }

        $this->jobProgressRepository->save($jobProgress);

        JobProgressTracker::instance()->set($jobProgress);

        try {
            CommandBusDispatcher::instance()->dispatch($command->command(), $user);
            $jobProgress->complete();
        } catch (\Throwable $e) {
            $jobProgress->fail();
            throw $e;
        } finally {
            JobProgressTracker::instance()->clear();
        }

        return null;
    }
}
