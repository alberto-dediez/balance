<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\Settlement;

use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesRequest;
use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesService;
use App\Payments\Domain\Model\Payout\PayoutCancelled;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UnsettleLedgerEntriesOnPayoutCancelledSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private UnsettleLedgerEntriesService $unsettleLedgerEntries)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PayoutCancelled);

        $this->unsettleLedgerEntries->__invoke(new UnsettleLedgerEntriesRequest($event->payoutId()));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PayoutCancelled;
    }
}
