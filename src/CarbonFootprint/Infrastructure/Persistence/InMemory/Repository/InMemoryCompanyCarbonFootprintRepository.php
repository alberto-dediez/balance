<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCompanyCarbonFootprintRepository implements CompanyCarbonFootprintRepository
{
    /** @param array<string, CompanyCarbonFootprint> $companyCarbonFootprints */
    public function __construct(
        private array $companyCarbonFootprints = [],
    ) {}

    public function add(CompanyCarbonFootprint $companyCarbonFootprint): void
    {
        if (isset($this->companyCarbonFootprints[(string) $companyCarbonFootprint->id()])) {
            return;
        }

        $this->companyCarbonFootprints[(string) $companyCarbonFootprint->id()] = $companyCarbonFootprint;
    }

    public function remove(CompanyCarbonFootprint $companyCarbonFootprint): void
    {
        if (!isset($this->companyCarbonFootprints[(string) $companyCarbonFootprint->id()])) {
            return;
        }

        unset($this->companyCarbonFootprints[(string) $companyCarbonFootprint->id()]);
    }

    public function find(CompanyCarbonFootprintId $companyCarbonFootprintId): ?CompanyCarbonFootprint
    {
        return (isset($this->companyCarbonFootprints[(string) $companyCarbonFootprintId]) && false == $this->companyCarbonFootprints[(string) $companyCarbonFootprintId]->isRemoved())
            ? $this->companyCarbonFootprints[(string) $companyCarbonFootprintId]
            : null
        ;
    }

    public function findByCompanyIdAndYear(CompanyId $companyId, int $year): ?CompanyCarbonFootprint
    {
        foreach ($this->companyCarbonFootprints as $companyCarbonFootprint) {
            if (true == $companyCarbonFootprint->isRemoved()) {
                continue;
            }

            $subjectCompanyId = $companyCarbonFootprint->companyId();

            if (null !== $subjectCompanyId && $subjectCompanyId->equals($companyId) && $companyCarbonFootprint->year() === $year) {
                return $companyCarbonFootprint;
            }
        }

        return null;
    }

    /** @return array<CompanyCarbonFootprint> */
    public function findByCompanyId(CompanyId $companyId): array
    {
        $results = [];

        foreach ($this->companyCarbonFootprints as $companyCarbonFootprint) {
            if (true == $companyCarbonFootprint->isRemoved()) {
                continue;
            }

            $subjectCompanyId = $companyCarbonFootprint->companyId();

            if (null !== $subjectCompanyId && $subjectCompanyId->equals($companyId)) {
                $results[] = $companyCarbonFootprint;
            }
        }

        return $results;
    }

    public function findByActivityId(ActivityId $activityId): ?CompanyCarbonFootprint
    {
        foreach ($this->companyCarbonFootprints as $companyCarbonFootprint) {
            if (true == $companyCarbonFootprint->isRemoved()) {
                continue;
            }

            foreach ($companyCarbonFootprint->activityReferences() as $reference) {
                if ($reference->activityId->equals($activityId)) {
                    return $companyCarbonFootprint;
                }
            }
        }

        return null;
    }

    /** @return array<int, CompanyCarbonFootprint> */
    public function findByFilter(CompanyCarbonFootprintFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->companyCarbonFootprints as $companyCarbonFootprint) {
            if (true == $companyCarbonFootprint->isRemoved()) {
                continue;
            }

            if ($filter->companyId instanceof CompanyId && (null === $companyCarbonFootprint->companyId() || !$filter->companyId->equals($companyCarbonFootprint->companyId()))) {
                continue;
            }

            if (null !== $filter->year && $companyCarbonFootprint->year() !== $filter->year) {
                continue;
            }

            if ($filter->status instanceof OrganizationalCarbonFootprintStatus && $companyCarbonFootprint->status() !== $filter->status) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $companyCarbonFootprint->accountId() || !$filter->accountId->equals($companyCarbonFootprint->accountId()))) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $companyCarbonFootprint;
        }

        return $results;
    }

    public function totalResultsByFilter(CompanyCarbonFootprintFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
