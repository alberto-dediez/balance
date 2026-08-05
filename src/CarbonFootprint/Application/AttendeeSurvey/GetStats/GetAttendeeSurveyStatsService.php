<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetAttendeeSurveyStatsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private EventRepository $eventRepository,
        private EventVenueLocator $eventVenueLocator,
    ) {}

    public function __invoke(GetAttendeeSurveyStatsRequest $request, User $user): GetAttendeeSurveyStatsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $eventId = $eventCarbonFootprint->eventId();
        $attendeeSurvey = null !== $eventId ? $this->attendeeSurveyRepository->findByEventId($eventId) : null;

        if (null === $attendeeSurvey) {
            throw new \InvalidArgumentException('AttendeeSurvey not found');
        }

        $responsesCount = $this->attendeeSurveyResponseRepository->countByAttendeeSurveyId($attendeeSurvey->id());
        $event = $this->eventRepository->find($eventId);
        $estimatedAttendees = $event?->estimatedAttendees();

        $surveyedPercentage = (null !== $estimatedAttendees && $estimatedAttendees > 0)
            ? min(100.0, ($responsesCount / $estimatedAttendees) * 100.0)
            : null;

        return new GetAttendeeSurveyStatsResponse(
            attendeeSurvey: $attendeeSurvey,
            responsesCount: $responsesCount,
            estimatedAttendees: $estimatedAttendees,
            surveyedPercentage: $surveyedPercentage,
            venueLocated: null !== $this->eventVenueLocator->locate($event),
            venueLabel: $event?->venueLabel(),
            legsByDistanceSource: $this->attendeeSurveyResponseRepository->countLegsByDistanceSource($attendeeSurvey->id()),
            multimodalResponsesCount: $this->attendeeSurveyResponseRepository->countMultimodalResponses($attendeeSurvey->id()),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetAttendeeSurveyStatsRequest;
    }
}
