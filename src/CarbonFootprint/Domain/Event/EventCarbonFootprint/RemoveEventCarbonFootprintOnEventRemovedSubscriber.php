<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Remove\RemoveEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Remove\RemoveEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveEventCarbonFootprintOnEventRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private RemoveEventCarbonFootprintService $removeEventCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventRemoved);

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->findByEventId($event->eventId());

        if (null === $eventCarbonFootprint) {
            return;
        }

        $request = new RemoveEventCarbonFootprintRequest($eventCarbonFootprint->id());

        ($this->removeEventCarbonFootprint)($request, $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventRemoved;
    }
}
