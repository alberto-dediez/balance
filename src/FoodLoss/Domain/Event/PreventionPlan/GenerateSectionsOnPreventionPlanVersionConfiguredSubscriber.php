<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\GenerateSections\GenerateSectionsRequest;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionConfigured;
use App\FoodLoss\Domain\Model\PreventionPlan\SectionsGenerator;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\TrackedCommand;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class GenerateSectionsOnPreventionPlanVersionConfiguredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionConfigured;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof PreventionPlanVersionConfigured);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new TrackedCommand(
                    command: new GenerateSectionsRequest(
                        preventionPlanId: $event->preventionPlanId(),
                    ),
                    progressKey: (string) $event->preventionPlanId(),
                    progressTotal: SectionsGenerator::DEFAULT_SECTIONS_COUNT,
                ),
            ),
            $this->loggedUser->__invoke(),
        );
    }
}
