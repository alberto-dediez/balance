<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\LedgerEntry;

use App\Balance\Application\LedgerEntry\Reassign\ReassignUnassignedLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\Reassign\ReassignUnassignedLedgerEntriesService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsReallocated;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReassignLedgerEntriesOnCreditsReallocatedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private ReassignUnassignedLedgerEntriesService $reassignLedgerEntries)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionCreditsReallocated);

        $this->reassignLedgerEntries->__invoke(
            new ReassignUnassignedLedgerEntriesRequest(
                projectContributionId: $event->projectContributionId(),
                lines: $event->lines(),
            ),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionCreditsReallocated;
    }
}
