<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetAdminDashboard;

use App\CarbonFootprint\Application\Dashboard\DashboardCompensationsCalculator;
use App\CarbonFootprint\Application\Dashboard\EmissionsBreakdownCalculator;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;

class GetAdminDashboardService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private UserRepository $userRepository,
        private EventRepository $eventRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EmissionsBreakdownCalculator $emissionsBreakdownCalculator,
        private DashboardCompensationsCalculator $dashboardCompensationsCalculator,
        private EventCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(GetAdminDashboardRequest $request, User $user): GetAdminDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
        ), SecurityAction::Admin, $user);

        $accounts = $this->accountRepository->findByFilter(new AccountFilter(limit: 0));
        $events = $this->eventRepository->findByFilter(new EventFilter(limit: 0));
        $footprints = $this->eventCarbonFootprintRepository->findByFilter(new EventCarbonFootprintFilter(limit: 0));

        $accountsById = [];
        $activeAccounts = 0;

        foreach ($accounts as $account) {
            $accountsById[(string) $account->id()] = $account;

            if ($account->isActive()) {
                ++$activeAccounts;
            }
        }

        $eventsById = [];
        $eventsThisYear = 0;
        $eventsByTypology = [];
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        foreach ($events as $event) {
            $eventsById[(string) $event->id()] = $event;

            $celebrationStart = $event->celebration()->startDate() ?? $event->registeredAt();
            if ($currentYear === (int) $celebrationStart->format('Y')) {
                ++$eventsThisYear;
            }

            $typology = $event->typology();
            if (null !== $typology) {
                $eventsByTypology[$typology->value] = ($eventsByTypology[$typology->value] ?? 0) + 1;
            }
        }

        $completedFootprints = 0;
        $totalEmissionsCo2eq = 0.0;
        $emissionsByYear = [];
        $emissionsByAccount = [];

        foreach ($footprints as $footprint) {
            if (!$footprint->status()->isCompleted()) {
                continue;
            }

            ++$completedFootprints;

            $co2eq = (float) $footprint->totalEmissions()->co2eq();
            $totalEmissionsCo2eq += $co2eq;

            $year = $footprint->calculationYear();
            $emissionsByYear[$year] = ($emissionsByYear[$year] ?? 0.0) + $co2eq;

            $accountId = $footprint->accountId();
            if (null !== $accountId && $co2eq > 0.0) {
                $emissionsByAccount[(string) $accountId] = ($emissionsByAccount[(string) $accountId] ?? 0.0) + $co2eq;
            }
        }

        ksort($emissionsByYear);

        arsort($emissionsByAccount);

        $topAccountsByEmissions = [];
        foreach (array_slice($emissionsByAccount, 0, 5, true) as $accountId => $co2eq) {
            $account = $accountsById[$accountId] ?? null;

            if ($account instanceof Account) {
                $topAccountsByEmissions[] = new AccountEmissions($account, $co2eq);
            }
        }

        $emissionsBreakdown = ($this->emissionsBreakdownCalculator)($footprints);

        usort($footprints, static fn(EventCarbonFootprint $a, EventCarbonFootprint $b): int => $b->registeredAt() <=> $a->registeredAt());
        $latestFootprints = array_map(
            static fn(EventCarbonFootprint $footprint): FootprintOverview => new FootprintOverview(
                $footprint,
                $eventsById[(string) $footprint->eventId()] ?? null,
                null !== $footprint->accountId() ? ($accountsById[(string) $footprint->accountId()] ?? null) : null,
            ),
            array_slice($footprints, 0, 5),
        );

        return new GetAdminDashboardResponse(
            totalAccounts: count($accounts),
            activeAccounts: $activeAccounts,
            totalUsers: $this->userRepository->totalResultsByFilter(new UserFilter()),
            totalEvents: count($events),
            eventsThisYear: $eventsThisYear,
            totalFootprints: count($footprints),
            completedFootprints: $completedFootprints,
            inProgressFootprints: count($footprints) - $completedFootprints,
            totalEmissionsCo2eq: $totalEmissionsCo2eq,
            averageEmissionsPerEventCo2eq: $completedFootprints > 0 ? $totalEmissionsCo2eq / $completedFootprints : 0.0,
            emissionsByYear: $emissionsByYear,
            emissionsByPhase: $emissionsBreakdown->byPhase,
            emissionsBySource: $emissionsBreakdown->bySource,
            topAccountsByEmissions: $topAccountsByEmissions,
            eventsByTypology: $eventsByTypology,
            latestFootprints: $latestFootprints,
            compensations: ($this->dashboardCompensationsCalculator)(null, $currentYear),
        );
    }
}
