<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\Contest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Confirm\ConfirmContestEntryService;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ConfirmContestEntryOnProjectContributionPaidSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ConfirmContestEntryService $confirmContestEntry,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionPaid);

        ($this->confirmContestEntry)(new ConfirmContestEntryRequest((string) $event->projectContributionId()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionPaid;
    }
}
