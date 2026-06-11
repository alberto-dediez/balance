<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class CompletePreventionPlanOnDocumentSignedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private PreventionPlanRepository $preventionPlanRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof DocumentSigned;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof DocumentSigned);

        $preventionPlan = $this->preventionPlanRepository->findBySignatureRequestId($event->signatureRequestId());

        if (null === $preventionPlan) {
            return;
        }

        $preventionPlan->markAsCompleted();
        $this->preventionPlanRepository->add($preventionPlan);
    }
}
