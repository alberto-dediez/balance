<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class CancelPurchasesOnProjectRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectRemoved);
        $this->handleProjectRemoved($event);
    }

    private function handleProjectRemoved(ProjectRemoved $event): void
    {
        $creditPurchases = $this->creditPurchaseRepository->findByFilter(
            new CreditPurchaseFilter(projectId: $event->projectId(), status: CreditPurchaseStatus::Pending, limit: 0),
        );

        foreach ($creditPurchases as $creditPurchase) {
            $creditPurchase->cancel();
            $this->creditPurchaseRepository->add($creditPurchase);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectRemoved;
    }
}
