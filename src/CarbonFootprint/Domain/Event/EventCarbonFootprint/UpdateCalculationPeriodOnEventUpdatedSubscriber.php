<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventUpdated;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UpdateCalculationPeriodOnEventUpdatedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EventRepository $eventRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private UpdateEventCarbonFootprintService $updateEventCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventUpdated);

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->findByEventId($event->eventId());

        if (null === $eventCarbonFootprint) {
            return;
        }

        $subjectEvent = $this->eventRepository->find($event->eventId());

        if (null === $subjectEvent) {
            return;
        }

        $calculationPeriod = EventCarbonFootprint::calculatePeriod($subjectEvent);
        $currentCalculationPeriod = $eventCarbonFootprint->calculationPeriod();

        if ($currentCalculationPeriod->startDate() == $calculationPeriod->startDate() && $currentCalculationPeriod->endDate() == $calculationPeriod->endDate()) {
            return;
        }

        $request = new UpdateEventCarbonFootprintRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            calculationPeriodStartDate: $calculationPeriod->startDate(),
            calculationPeriodEndDate: $calculationPeriod->endDate(),
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
            status: $eventCarbonFootprint->status(),
        );

        ($this->updateEventCarbonFootprint)($request, $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventUpdated;
    }
}
