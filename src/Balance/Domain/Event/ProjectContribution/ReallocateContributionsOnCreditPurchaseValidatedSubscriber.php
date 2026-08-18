<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReallocateContributionsOnCreditPurchaseValidatedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ReallocateUnderallocatedContributionsService $reallocateContributions,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CreditPurchaseValidated);

        $creditPurchase = $this->creditPurchaseRepository->find($event->creditPurchaseId());

        if (null === $creditPurchase) {
            return;
        }

        $accountId = $creditPurchase->accountId();
        $projectId = $creditPurchase->projectId();

        if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
            return;
        }

        $this->reallocateContributions->__invoke(
            new ReallocateUnderallocatedContributionsRequest($accountId, $projectId),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CreditPurchaseValidated;
    }
}
