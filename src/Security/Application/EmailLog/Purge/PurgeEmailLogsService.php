<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Purge;

use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class PurgeEmailLogsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly EmailLogRepository $repository,
        private readonly int $retentionDays,
    ) {}

    public function __invoke(PurgeEmailLogsRequest $request, User $user): PurgeEmailLogsResponse
    {
        $limit = $request->now->modify(sprintf('-%d days', $this->retentionDays));

        $removed = 0;

        foreach ($this->repository->findSentBefore($limit) as $emailLog) {
            $emailLog->remove();
            $this->repository->remove($emailLog);
            ++$removed;
        }

        return new PurgeEmailLogsResponse(removed: $removed);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeEmailLogsRequest;
    }
}
