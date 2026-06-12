<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Dashboard;

use App\FoodLoss\Application\Dashboard\DashboardChart;
use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionStatus;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetPreventionPlanDashboardService
{
    public function __construct(
        private readonly PreventionPlanRepository $repository,
        private readonly PreventionPlanAuthorizer $authorizer,
        private readonly CompanyRepository $companyRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function __invoke(GetPreventionPlanDashboardRequest $request, User $user): GetPreventionPlanDashboardResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PreventionPlan(id: PreventionPlanId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $request->limit = 0;
        $request->page = 0;

        $preventionPlans = $this->repository->findByFilter($request);

        $completedPreventionPlans = 0;
        $inProgressPreventionPlans = 0;
        $withResponsiblePreventionPlans = 0;

        $countByStatus = [];

        foreach (PreventionPlanVersionStatus::cases() as $status) {
            $countByStatus[$status->value] = 0;
        }

        $countByMonth = [];
        $companyIdsWithPlan = [];
        $propertyIdsWithPlan = [];

        foreach ($preventionPlans as $preventionPlan) {
            $status = $preventionPlan->status();
            ++$countByStatus[$status->value];

            if ($preventionPlan->lastCompletedVersion() instanceof PreventionPlanVersion) {
                ++$completedPreventionPlans;
            }

            if (PreventionPlanVersionStatus::Completed !== $status && PreventionPlanVersionStatus::Abandoned !== $status) {
                ++$inProgressPreventionPlans;
            }

            if ($preventionPlan->responsible() instanceof EmployeeId) {
                ++$withResponsiblePreventionPlans;
            }

            $month = $preventionPlan->registeredAt()->format('Y-m');
            $countByMonth[$month] = ($countByMonth[$month] ?? 0) + 1;

            $companyIdsWithPlan[(string) $preventionPlan->companyId()] = true;

            foreach ($preventionPlan->propertyIds() as $propertyId) {
                $propertyIdsWithPlan[(string) $propertyId] = true;
            }
        }

        ksort($countByMonth);

        $allCompanies = $this->companyRepository->findByFilter(new CompanyFilter(accountId: $request->accountId, limit: 0));
        $companiesWithPlan = 0;
        $companiesWithoutPlan = 0;

        foreach ($allCompanies as $company) {
            if (isset($companyIdsWithPlan[(string) $company->id()])) {
                ++$companiesWithPlan;
            } else {
                ++$companiesWithoutPlan;
            }
        }

        $allProperties = $this->propertyRepository->findByFilter(new PropertyFilter(accountId: $request->accountId, limit: 0));
        $propertiesWithPlan = 0;
        $propertiesWithoutPlan = 0;

        foreach ($allProperties as $property) {
            if (isset($propertyIdsWithPlan[(string) $property->id()])) {
                ++$propertiesWithPlan;
            } else {
                ++$propertiesWithoutPlan;
            }
        }

        return new GetPreventionPlanDashboardResponse(
            totalPreventionPlans: count($preventionPlans),
            completedPreventionPlans: $completedPreventionPlans,
            inProgressPreventionPlans: $inProgressPreventionPlans,
            withResponsiblePreventionPlans: $withResponsiblePreventionPlans,
            byStatus: DashboardChart::fromCounts($countByStatus),
            registrationsByMonth: DashboardChart::fromCounts($this->fillMonthGaps($countByMonth)),
            byCompany: DashboardChart::fromCounts([
                'Con plan' => $companiesWithPlan,
                'Sin plan' => $companiesWithoutPlan,
            ]),
            properties: DashboardChart::fromCounts([
                'Con plan' => $propertiesWithPlan,
                'Sin plan' => $propertiesWithoutPlan,
            ]),
        );
    }

    /**
     * @param array<string, int> $countByMonth indexed by 'Y-m'
     *
     * @return array<string, int> indexed by 'm/Y' with empty months filled
     */
    private function fillMonthGaps(array $countByMonth): array
    {
        if ([] === $countByMonth) {
            return [];
        }

        $months = array_keys($countByMonth);

        $current = new \DateTimeImmutable(sprintf('%s-01', $months[0]));
        $last = new \DateTimeImmutable(sprintf('%s-01', $months[count($months) - 1]));

        $result = [];

        while ($current <= $last) {
            $result[$current->format('m/Y')] = $countByMonth[$current->format('Y-m')] ?? 0;

            $current = $current->modify('+1 month');
        }

        return $result;
    }
}
