<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\Contest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFailed;
use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\RemovePending\RemovePendingContestEntryService;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveContestEntryOnProjectContributionFailedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemovePendingContestEntryService $removePendingContestEntry,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionFailed);

        ($this->removePendingContestEntry)(new RemovePendingContestEntryRequest((string) $event->projectContributionId()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionFailed;
    }
}
