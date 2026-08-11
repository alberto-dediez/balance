<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
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
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectContributionsAggregator $contributionsAggregator,
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
            contributions: $this->contributions($attendeeSurvey->id()),
            venueLocated: null !== $this->eventVenueLocator->locate($event),
            venueLabel: $event?->venueLabel(),
            legsByDistanceSource: $this->attendeeSurveyResponseRepository->countLegsByDistanceSource($attendeeSurvey->id()),
            multimodalResponsesCount: $this->attendeeSurveyResponseRepository->countMultimodalResponses($attendeeSurvey->id()),
        );
    }

    /**
     * The contributions of the survey are the ones started from its answers, so the rates compare
     * like with like even when the attendee paid days after answering.
     */
    private function contributions(AttendeeSurveyId $attendeeSurveyId): ContributionsSummary
    {
        $responses = $this->attendeeSurveyResponseRepository->findByAttendeeSurveyId($attendeeSurveyId);
        $originIds = array_map(static fn(AttendeeSurveyResponse $response): string => (string) $response->id(), $responses);

        $contributions = [] === $originIds
            ? $this->contributionsAggregator->aggregate([])
            : $this->contributionsAggregator->aggregate($this->projectContributionRepository->findByFilter(
                new ProjectContributionFilter(
                    origin: ProjectContributionOrigin::AttendeeSurveyResponse,
                    originIds: $originIds,
                    limit: 0,
                ),
            ));

        return new ContributionsSummary(
            aggregation: $contributions,
            startedRate: $this->rate($contributions->contributions, count($responses)),
            paidRate: $this->rate($contributions->paid, count($responses)),
            completionRate: $this->rate($contributions->paid, $contributions->contributions),
            compensatedEmissionsRate: $this->compensatedEmissionsRate($contributions, $responses),
        );
    }

    /**
     * The rate is measured against the sum of the individual footprints, not against the total of the
     * event: that one is extrapolated to every attendee and would make the rate incomparable.
     *
     * @param list<AttendeeSurveyResponse> $responses
     */
    private function compensatedEmissionsRate(ProjectContributionsAggregation $contributions, array $responses): float
    {
        $emitted = 0.0;

        foreach ($responses as $response) {
            $emitted += (float) $response->emissions()->co2eq();
        }

        if ($emitted <= 0.0) {
            return 0.0;
        }

        return $contributions->paidTonnes * 1000 * 100 / $emitted;
    }

    private function rate(int $part, int $total): float
    {
        return 0 === $total ? 0.0 : $part * 100 / $total;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetAttendeeSurveyStatsRequest;
    }
}
