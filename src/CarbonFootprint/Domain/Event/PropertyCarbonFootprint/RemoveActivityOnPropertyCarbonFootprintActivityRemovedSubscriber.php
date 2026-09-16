<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityRequest;
use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintActivityRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private RemoveActivityService $removeActivity, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintActivityRemoved);

        $request = new RemoveActivityRequest($event->activityId());

        ($this->removeActivity)($request, $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintActivityRemoved;
    }
}
