<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payment\PaymentExpired;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ExpireContributionOnPaymentExpiredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ExpireProjectContributionService $expireProjectContribution,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PaymentExpired);

        $this->expireProjectContribution->__invoke(
            new ExpireProjectContributionRequest(new ProjectContributionId($event->subjectId())),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PaymentExpired
            && RegisterProjectContributionService::SUBJECT_TYPE === $event->subjectType();
    }
}
