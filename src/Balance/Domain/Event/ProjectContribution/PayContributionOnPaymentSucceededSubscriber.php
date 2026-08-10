<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payment\PaymentSucceeded;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class PayContributionOnPaymentSucceededSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ConfirmProjectContributionService $confirmProjectContribution,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PaymentSucceeded);

        $this->confirmProjectContribution->__invoke(
            new ConfirmProjectContributionRequest(new ProjectContributionId($event->subjectId())),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PaymentSucceeded
            && RegisterProjectContributionService::SUBJECT_TYPE === $event->subjectType();
    }
}
