<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\LoadActions\LoadActionsRequest;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCreated;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class LoadActionsOnPreventionPlanVersionCreatedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionCreated;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof PreventionPlanVersionCreated);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new LoadActionsRequest(
                    preventionPlanId: $event->preventionPlanId(),
                ),
            ),
            $this->loggedUser->__invoke(),
        );
    }
}
