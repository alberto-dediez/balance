<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Messenger;

use App\Balance\Application\ProjectContribution\Expire\ContributionExpirationScheduler;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionCommand;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final class MessengerContributionExpirationScheduler implements ContributionExpirationScheduler
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function scheduleExpiration(ProjectContributionId $projectContributionId, \DateTimeImmutable $at): void
    {
        $delayInMilliseconds = ($at->getTimestamp() - time()) * 1000;

        $this->messageBus->dispatch(
            new ExpireProjectContributionCommand((string) $projectContributionId),
            $delayInMilliseconds > 0 ? [new DelayStamp($delayInMilliseconds)] : [],
        );
    }
}
