<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

use App\CarbonFootprint\Application\Dashboard\DashboardCompensationsCalculator;
use App\CarbonFootprint\Application\Dashboard\EmissionsBreakdownCalculator;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetOwnerDashboardService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private EmissionsBreakdownCalculator $emissionsBreakdownCalculator,
        private DashboardCompensationsCalculator $dashboardCompensationsCalculator,
        private EventCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(GetOwnerDashboardRequest $request, User $user): GetOwnerDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        $events = $this->eventRepository->findByFilter(new EventFilter(accountId: $request->accountId, limit: 0));
        $footprints = $this->eventCarbonFootprintRepository->findByFilter(new EventCarbonFootprintFilter(accountId: $request->accountId, limit: 0));

        $eventsById = [];
        $eventsThisYear = 0;
        $totalEstimatedAttendees = 0;
        $eventsByTypology = [];
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        foreach ($events as $event) {
            $eventsById[(string) $event->id()] = $event;

            $celebrationStart = $event->celebration()->startDate() ?? $event->registeredAt();
            if ($currentYear === (int) $celebrationStart->format('Y')) {
                ++$eventsThisYear;
            }

            $totalEstimatedAttendees += (int) $event->estimatedAttendees();

            $typology = $event->typology();
            if (null !== $typology) {
                $eventsByTypology[$typology->value] = ($eventsByTypology[$typology->value] ?? 0) + 1;
            }
        }

        $completedFootprints = 0;
        $totalEmissionsCo2eq = 0.0;
        $emissionsPerAttendeeSum = 0.0;
        $emissionsByYear = [];
        $topEventsByEmissions = [];
        $emissionsByProperty = [];
        $emissionsByCompany = [];
        $propertyNames = [];
        $companyNames = [];

        foreach ($footprints as $footprint) {
            if (!$footprint->status()->isCompleted()) {
                continue;
            }

            ++$completedFootprints;

            $co2eq = (float) $footprint->totalEmissions()->co2eq();
            $totalEmissionsCo2eq += $co2eq;
            $emissionsPerAttendeeSum += (float) $footprint->emissionsPerAttendee()->co2eq();

            $year = $footprint->calculationYear();
            $emissionsByYear[$year] = ($emissionsByYear[$year] ?? 0.0) + $co2eq;

            $event = $eventsById[(string) $footprint->eventId()] ?? null;
            if ($event instanceof Event && $co2eq > 0.0) {
                $topEventsByEmissions[] = new EventEmissions($event, $co2eq);

                $propertyId = $event->propertyId();
                if (null !== $propertyId) {
                    $propertyKey = (string) $propertyId;
                    if (!isset($propertyNames[$propertyKey])) {
                        $propertyNames[$propertyKey] = $this->propertyRepository->find($propertyId)?->name() ?? $propertyKey;
                    }
                    $emissionsByProperty[$propertyNames[$propertyKey]] = ($emissionsByProperty[$propertyNames[$propertyKey]] ?? 0.0) + $co2eq;
                }

                $companyId = $event->companyId();
                if (null !== $companyId) {
                    $companyKey = (string) $companyId;
                    if (!isset($companyNames[$companyKey])) {
                        $companyNames[$companyKey] = $this->companyRepository->find($companyId)?->name() ?? $companyKey;
                    }
                    $emissionsByCompany[$companyNames[$companyKey]] = ($emissionsByCompany[$companyNames[$companyKey]] ?? 0.0) + $co2eq;
                }
            }
        }

        ksort($emissionsByYear);
        arsort($emissionsByProperty);
        arsort($emissionsByCompany);

        usort($topEventsByEmissions, static fn(EventEmissions $a, EventEmissions $b): int => $b->co2eq <=> $a->co2eq);
        $topEventsByEmissions = array_slice($topEventsByEmissions, 0, 5);

        $emissionsBreakdown = ($this->emissionsBreakdownCalculator)($footprints);

        usort($footprints, static fn(EventCarbonFootprint $a, EventCarbonFootprint $b): int => $b->registeredAt() <=> $a->registeredAt());
        $latestFootprints = array_map(
            static fn(EventCarbonFootprint $footprint): FootprintOverview => new FootprintOverview(
                $footprint,
                $eventsById[(string) $footprint->eventId()] ?? null,
            ),
            array_slice($footprints, 0, 5),
        );

        return new GetOwnerDashboardResponse(
            totalEvents: count($events),
            eventsThisYear: $eventsThisYear,
            totalEstimatedAttendees: $totalEstimatedAttendees,
            totalFootprints: count($footprints),
            completedFootprints: $completedFootprints,
            inProgressFootprints: count($footprints) - $completedFootprints,
            totalEmissionsCo2eq: $totalEmissionsCo2eq,
            averageEmissionsPerEventCo2eq: $completedFootprints > 0 ? $totalEmissionsCo2eq / $completedFootprints : 0.0,
            averageEmissionsPerAttendeeCo2eq: $completedFootprints > 0 ? $emissionsPerAttendeeSum / $completedFootprints : 0.0,
            emissionsByYear: $emissionsByYear,
            emissionsByPhase: $emissionsBreakdown->byPhase,
            emissionsBySource: $emissionsBreakdown->bySource,
            topEventsByEmissions: $topEventsByEmissions,
            eventsByTypology: $eventsByTypology,
            latestFootprints: $latestFootprints,
            emissionsByProperty: $emissionsByProperty,
            emissionsByCompany: $emissionsByCompany,
            compensations: ($this->dashboardCompensationsCalculator)($request->accountId, $currentYear),
        );
    }
}
