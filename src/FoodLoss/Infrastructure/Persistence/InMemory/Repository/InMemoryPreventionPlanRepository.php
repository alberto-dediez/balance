<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPreventionPlanRepository implements PreventionPlanRepository
{
    /** @param array<string, PreventionPlan> $preventionPlans */
    public function __construct(
        private array $preventionPlans = [],
    ) {}

    public function add(PreventionPlan $preventionPlan): void
    {
        $this->preventionPlans[(string) $preventionPlan->id()] = $preventionPlan;
    }

    public function remove(PreventionPlan $preventionPlan): void
    {
        if (!isset($this->preventionPlans[(string) $preventionPlan->id()])) {
            return;
        }

        unset($this->preventionPlans[(string) $preventionPlan->id()]);
    }

    public function find(PreventionPlanId $preventionPlanId): ?PreventionPlan
    {
        return (isset($this->preventionPlans[(string) $preventionPlanId]) && false == $this->preventionPlans[(string) $preventionPlanId]->isRemoved())
            ? $this->preventionPlans[(string) $preventionPlanId]
            : null
        ;
    }

    public function findBySignatureRequestId(SignatureRequestId $signatureRequestId): ?PreventionPlan
    {
        foreach ($this->preventionPlans as $preventionPlan) {
            if ($preventionPlan->isRemoved()) {
                continue;
            }

            $versionSignatureRequestId = $preventionPlan->currentPlanVersion()->signatureRequestId();

            if (null !== $versionSignatureRequestId && $versionSignatureRequestId->equals($signatureRequestId)) {
                return $preventionPlan;
            }
        }

        return null;
    }

    /** @return array<int, PreventionPlan> */
    public function findByFilter(PreventionPlanFilter $filter): array
    {
        $results = [];

        if (0 === count($this->preventionPlans)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->preventionPlans as $preventionPlan) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $preventionPlan->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->companyId instanceof CompanyId) {
                $planCompanyId = $preventionPlan->companyId();
                if (!$filter->companyId->equals($planCompanyId)) {
                    continue;
                }
            }

            if ($filter->accountId instanceof AccountId) {
                $planAccountId = $preventionPlan->accountId();
                if (!$planAccountId instanceof AccountId || !$filter->accountId->equals($planAccountId)) {
                    continue;
                }
            }

            if ($filter->propertyId instanceof PropertyId) {
                $propertyIds = $preventionPlan->propertyIds();
                if (count($propertyIds) > 0) {
                    $found = false;
                    foreach ($propertyIds as $propertyId) {
                        if ($filter->propertyId->equals($propertyId)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        continue;
                    }
                }
            }

            if ($filter->criticalPointId instanceof CriticalPointId) {
                $hasCriticalPoint = false;
                $currentVersion = $preventionPlan->currentPlanVersion();
                foreach ($currentVersion->proposedActions()->toArray() as $pa) {
                    if ($pa->criticalPointId()->equals($filter->criticalPointId)) {
                        $hasCriticalPoint = true;
                        break;
                    }
                }
                if (!$hasCriticalPoint) {
                    continue;
                }
            }

            if (true == $preventionPlan->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $preventionPlan;
        }

        return $results;
    }

    public function totalResultsByFilter(PreventionPlanFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
