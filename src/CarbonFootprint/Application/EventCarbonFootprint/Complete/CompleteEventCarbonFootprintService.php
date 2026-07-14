<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Complete;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CompleteEventCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ActivityRepository $activityRepository,
        private EventRepository $eventRepository,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
    ) {}

    public function __invoke(CompleteEventCarbonFootprintRequest $request, User $user): void
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $activityIds = $eventCarbonFootprint->activities()->activityIds();

        $total = new Emissions();
        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $total = $total->add($activity->emissions());
        }

        $eventId = $eventCarbonFootprint->eventId();

        $attendees = null !== $eventId ? $this->attendeeSurveyRepository->findByEventId($eventId)?->realAttendees() : null;

        if (null === $attendees || $attendees <= 0) {
            $attendees = null !== $eventId ? $this->eventRepository->find($eventId)?->estimatedAttendees() : null;
        }

        $attendees ??= 0;

        $perAttendee = ($attendees > 0) ? $total->dividedBy($attendees) : new Emissions();
        $eventCarbonFootprint->complete(
            totalEmissions: $total,
            emissionsPerAttendee: $perAttendee,
        );

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof CompleteEventCarbonFootprintRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CompleteEventCarbonFootprintRequest;
    }
}
