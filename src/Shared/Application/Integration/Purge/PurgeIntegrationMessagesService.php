<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration\Purge;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;

class PurgeIntegrationMessagesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly IntegrationOutboxRepository $outbox,
        private readonly IntegrationInboxRepository $inbox,
        private readonly int $retentionDays,
    ) {}

    public function __invoke(PurgeIntegrationMessagesRequest $request, User $user): PurgeIntegrationMessagesResponse
    {
        $limit = $request->now->modify(sprintf('-%d days', $this->retentionDays));

        return new PurgeIntegrationMessagesResponse(
            outboxRemoved: $this->outbox->purgeSentBefore($limit),
            inboxRemoved: $this->inbox->purgeReceivedBefore($limit),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeIntegrationMessagesRequest;
    }
}
