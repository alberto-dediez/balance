<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Application\Event\Lock\LockEventRequest;
use App\EventOperations\Application\Event\Lock\LockEventService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class LockEventOnEventCarbonFootprintCompletedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private LockEventService $lockEvent,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventCarbonFootprintCompleted);

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($event->eventCarbonFootprintId());
        $eventId = $eventCarbonFootprint?->eventId();

        if (null === $eventId) {
            return;
        }

        ($this->lockEvent)(new LockEventRequest($eventId), $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintCompleted;
    }
}
