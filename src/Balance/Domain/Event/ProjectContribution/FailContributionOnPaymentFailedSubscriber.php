<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Fail\FailProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Fail\FailProjectContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payment\PaymentFailed;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class FailContributionOnPaymentFailedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private FailProjectContributionService $failProjectContribution,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PaymentFailed);

        $this->failProjectContribution->__invoke(
            new FailProjectContributionRequest(new ProjectContributionId($event->subjectId()), $event->reason()),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PaymentFailed
            && RegisterProjectContributionService::SUBJECT_TYPE === $event->subjectType();
    }
}
