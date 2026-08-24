<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyFilter;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkFilter;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Security\Domain\Model\Account\AccountId;

final class DashboardCompensationsCalculator
{
    private const int TOP_PROJECTS = 5;

    private const array VOLUNTARY_ORIGINS = [
        ProjectContributionOrigin::AttendeeSurveyResponse,
        ProjectContributionOrigin::PropertyTripCalculation,
    ];

    /** @var array<string, ProjectType|null> */
    private array $projectTypes = [];

    /** @var array<string, string> */
    private array $projectNames = [];

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectContributionsAggregator $contributionsAggregator,
        private ProjectRepository $projectRepository,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationRepository $tripCalculationRepository,
    ) {}

    public function __invoke(?AccountId $accountId, int $periodYear): DashboardCompensations
    {
        $contributions = $this->projectContributionRepository->findByFilter(
            new ProjectContributionFilter(accountId: $accountId, limit: 0),
        );

        $aggregation = $this->contributionsAggregator->aggregate($contributions);

        $periodCompensatedCo2eqKg = 0.0;
        $periodFundsAmount = 0.0;
        $socialFundsAmount = 0.0;
        $voluntaryCompensatedCo2eqKg = 0.0;
        $ownCompensatedCo2eqKg = 0.0;
        $voluntaryPaidContributions = 0;
        $voluntaryOriginIds = [];
        $compensatedByYear = [];
        $fundsByProject = [];

        foreach ($contributions as $contribution) {
            if (!$contribution->isPaid()) {
                continue;
            }

            $co2eqKg = $contribution->tonnes() * 1000;
            $amount = $contribution->isOwnCreditFunded() ? 0.0 : $contribution->totalAmount();
            $year = (int) ($contribution->paidAt() ?? $contribution->registeredAt())->format('Y');

            $compensatedByYear[$year] = ($compensatedByYear[$year] ?? 0.0) + $co2eqKg;

            if ($periodYear === $year) {
                $periodCompensatedCo2eqKg += $co2eqKg;
                $periodFundsAmount += $amount;
            }

            if (ProjectType::SocialProject === $this->projectType($contribution->projectId())) {
                $socialFundsAmount += $amount;
            }

            if ($amount > 0.0) {
                $projectName = $this->projectName($contribution->projectId());
                $fundsByProject[$projectName] = ($fundsByProject[$projectName] ?? 0.0) + $amount;
            }

            if (in_array($contribution->origin(), self::VOLUNTARY_ORIGINS, true)) {
                ++$voluntaryPaidContributions;
                $voluntaryCompensatedCo2eqKg += $co2eqKg;

                $originId = $contribution->originId();
                if (null !== $originId) {
                    $voluntaryOriginIds[$originId] = true;
                }

                continue;
            }

            $ownCompensatedCo2eqKg += $co2eqKg;
        }

        ksort($compensatedByYear);
        arsort($fundsByProject);

        $attendeeSurveyIds = $this->attendeeSurveyIds($accountId);
        $tripCalculationLinkIds = $this->tripCalculationLinkIds($accountId);
        $surveyResponses = $this->attendeeSurveyResponseRepository->countByAttendeeSurveyIds($attendeeSurveyIds);
        $tripCalculations = $this->tripCalculations($tripCalculationLinkIds);
        $voluntaryContributors = count($voluntaryOriginIds);
        $participants = $surveyResponses + $tripCalculations;

        return new DashboardCompensations(
            aggregation: $aggregation,
            compensatedCo2eqKg: $aggregation->paidTonnes * 1000,
            periodCompensatedCo2eqKg: $periodCompensatedCo2eqKg,
            fundsAmount: $aggregation->paidTotalAmount,
            periodFundsAmount: $periodFundsAmount,
            socialFundsAmount: $socialFundsAmount,
            voluntaryCompensatedCo2eqKg: $voluntaryCompensatedCo2eqKg,
            ownCompensatedCo2eqKg: $ownCompensatedCo2eqKg,
            voluntaryPaidContributions: $voluntaryPaidContributions,
            voluntaryContributors: $voluntaryContributors,
            attendeeSurveys: count($attendeeSurveyIds),
            surveyResponses: $surveyResponses,
            tripCalculationLinks: count($tripCalculationLinkIds),
            tripCalculations: $tripCalculations,
            voluntaryParticipationRate: 0 === $participants ? 0.0 : min(100.0, $voluntaryContributors * 100 / $participants),
            compensatedByYear: $compensatedByYear,
            fundsByProject: array_slice($fundsByProject, 0, self::TOP_PROJECTS, true),
        );
    }

    /** @return list<AttendeeSurveyId> */
    private function attendeeSurveyIds(?AccountId $accountId): array
    {
        return array_map(
            static fn(AttendeeSurvey $attendeeSurvey): AttendeeSurveyId => $attendeeSurvey->id(),
            $this->attendeeSurveyRepository->findByFilter(new AttendeeSurveyFilter(accountId: $accountId, limit: 0)),
        );
    }

    /** @return list<TripCalculationLinkId> */
    private function tripCalculationLinkIds(?AccountId $accountId): array
    {
        return array_map(
            static fn(TripCalculationLink $tripCalculationLink): TripCalculationLinkId => $tripCalculationLink->id(),
            $this->tripCalculationLinkRepository->findByFilter(new TripCalculationLinkFilter(accountId: $accountId, limit: 0)),
        );
    }

    /** @param list<TripCalculationLinkId> $tripCalculationLinkIds */
    private function tripCalculations(array $tripCalculationLinkIds): int
    {
        if ([] === $tripCalculationLinkIds) {
            return 0;
        }

        return $this->tripCalculationRepository->totalResultsByFilter(
            new TripCalculationFilter(tripCalculationLinkIds: $tripCalculationLinkIds, limit: 0),
        );
    }

    private function projectType(?ProjectId $projectId): ?ProjectType
    {
        if (!$projectId instanceof ProjectId) {
            return null;
        }

        $key = (string) $projectId;

        if (!array_key_exists($key, $this->projectTypes)) {
            $this->projectTypes[$key] = $this->projectRepository->find($projectId)?->projectType();
        }

        return $this->projectTypes[$key];
    }

    private function projectName(?ProjectId $projectId): string
    {
        if (!$projectId instanceof ProjectId) {
            return '—';
        }

        $key = (string) $projectId;

        if (!isset($this->projectNames[$key])) {
            $this->projectNames[$key] = $this->projectRepository->find($projectId)?->name() ?? $key;
        }

        return $this->projectNames[$key];
    }
}
