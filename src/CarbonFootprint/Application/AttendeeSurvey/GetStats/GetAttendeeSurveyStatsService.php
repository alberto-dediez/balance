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
use App\CarbonFootprint\Application\Trip\MobilityBreakdownCalculator;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownRow;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
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

    private const int TOP_ORIGINS = 8;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private EventRepository $eventRepository,
        private EventVenueLocator $eventVenueLocator,
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectContributionsAggregator $contributionsAggregator,
        private TripLegsAccumulator $legsAccumulator,
        private MobilityBreakdownCalculator $mobilityBreakdownCalculator,
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

        $responses = $this->attendeeSurveyResponseRepository->findByAttendeeSurveyId($attendeeSurvey->id());
        $responsesCount = $this->attendeeSurveyResponseRepository->countByAttendeeSurveyId($attendeeSurvey->id());
        $event = $this->eventRepository->find($eventId);
        $estimatedAttendees = $event?->estimatedAttendees();

        $surveyedPercentage = (null !== $estimatedAttendees && $estimatedAttendees > 0)
            ? min(100.0, ($responsesCount / $estimatedAttendees) * 100.0)
            : null;

        $totalEmissions = $this->sumEmissions($responses, static fn(AttendeeSurveyResponse $response): Emissions => $response->emissions());

        return new GetAttendeeSurveyStatsResponse(
            attendeeSurvey: $attendeeSurvey,
            responsesCount: $responsesCount,
            estimatedAttendees: $estimatedAttendees,
            surveyedPercentage: $surveyedPercentage,
            contributions: $this->contributions($responses),
            venueLocated: null !== $this->eventVenueLocator->locate($event),
            venueLabel: $event?->venueLabel(),
            legsByDistanceSource: $this->attendeeSurveyResponseRepository->countLegsByDistanceSource($attendeeSurvey->id()),
            multimodalResponsesCount: $this->attendeeSurveyResponseRepository->countMultimodalResponses($attendeeSurvey->id()),
            totalEmissions: $totalEmissions,
            averageEmissions: [] === $responses ? new Emissions() : $totalEmissions->dividedBy(count($responses)),
            mobilityEmissions: $this->sumEmissions($responses, static fn(AttendeeSurveyResponse $response): Emissions => $response->mobilityEmissions()),
            overnightEmissions: $this->sumEmissions($responses, static fn(AttendeeSurveyResponse $response): Emissions => $response->overnightEmissions()),
            mobilityBreakdown: $this->mobilityBreakdown($responses),
            responsesByDay: $this->responsesByDay($responses),
            topOrigins: $this->topOrigins($responses),
            fromAnotherCityCount: count(array_filter($responses, static fn(AttendeeSurveyResponse $response): bool => $response->cameFromAnotherCity())),
            usedHotelCount: count(array_filter($responses, static fn(AttendeeSurveyResponse $response): bool => $response->usedHotel())),
            movedWithinCityCount: count(array_filter($responses, static fn(AttendeeSurveyResponse $response): bool => $response->movedWithinCity())),
            totalNights: array_sum(array_map(static fn(AttendeeSurveyResponse $response): int => $response->hotelNights() ?? 0, $responses)),
        );
    }

    /**
     * @param list<AttendeeSurveyResponse>                $responses
     * @param callable(AttendeeSurveyResponse): Emissions $emissions
     */
    private function sumEmissions(array $responses, callable $emissions): Emissions
    {
        $total = new Emissions();

        foreach ($responses as $response) {
            $total = $total->add($emissions($response));
        }

        return $total;
    }

    /**
     * @param list<AttendeeSurveyResponse> $responses
     *
     * @return list<MobilityBreakdownRow>
     */
    private function mobilityBreakdown(array $responses): array
    {
        $kmByMobilityId = [];

        foreach ($responses as $response) {
            $this->legsAccumulator->accumulate(
                $kmByMobilityId,
                $response->legsOfKind(LegKind::Intercity),
                $response->intercityTrips(),
                $response->intercityRoundTrip(),
            );

            $this->legsAccumulator->accumulate(
                $kmByMobilityId,
                $response->legsOfKind(LegKind::City),
                $response->cityTrips(),
                $response->cityRoundTrip(),
            );
        }

        [$emissionFactorGroupId, $calculationYear] = $this->factorContext($responses);

        return $this->mobilityBreakdownCalculator->calculate($kmByMobilityId, $emissionFactorGroupId, $calculationYear);
    }

    /**
     * @param list<AttendeeSurveyResponse> $responses
     *
     * @return array{0: EmissionFactorGroupId|null, 1: int|null}
     */
    private function factorContext(array $responses): array
    {
        foreach ($responses as $response) {
            $emissionFactorGroupId = $response->emissionFactorGroupId();
            $calculationYear = $response->calculationYear();

            if (null !== $emissionFactorGroupId && null !== $calculationYear) {
                return [$emissionFactorGroupId, $calculationYear];
            }
        }

        return [null, null];
    }

    /**
     * @param list<AttendeeSurveyResponse> $responses
     *
     * @return array<string, int>
     */
    private function responsesByDay(array $responses): array
    {
        $byDay = [];

        foreach ($responses as $response) {
            $day = $response->registeredAt()->format('Y-m-d');
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        }

        ksort($byDay);

        return $byDay;
    }

    /**
     * @param list<AttendeeSurveyResponse> $responses
     *
     * @return array<string, int>
     */
    private function topOrigins(array $responses): array
    {
        $byOrigin = [];

        foreach ($responses as $response) {
            $origin = $response->originLabel() ?? $response->cityOriginLabel();

            if (null === $origin || '' === trim($origin)) {
                continue;
            }

            $byOrigin[$origin] = ($byOrigin[$origin] ?? 0) + 1;
        }

        arsort($byOrigin);

        return array_slice($byOrigin, 0, self::TOP_ORIGINS, true);
    }

    /** @param list<AttendeeSurveyResponse> $responses */
    private function contributions(array $responses): ContributionsSummary
    {
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
