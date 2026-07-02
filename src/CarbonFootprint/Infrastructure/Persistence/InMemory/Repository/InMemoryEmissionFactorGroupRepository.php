<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEmissionFactorGroupRepository implements EmissionFactorGroupRepository
{
    /** @param array<string, EmissionFactorGroup> $emissionFactorGroups */
    public function __construct(
        private array $emissionFactorGroups = [],
    ) {}

    public function add(EmissionFactorGroup $emissionFactorGroup): void
    {
        if (isset($this->emissionFactorGroups[(string) $emissionFactorGroup->id()])) {
            return;
        }

        $this->emissionFactorGroups[(string) $emissionFactorGroup->id()] = $emissionFactorGroup;
    }

    public function remove(EmissionFactorGroup $emissionFactorGroup): void
    {
        if (!isset($this->emissionFactorGroups[(string) $emissionFactorGroup->id()])) {
            return;
        }

        unset($this->emissionFactorGroups[(string) $emissionFactorGroup->id()]);
    }

    public function find(EmissionFactorGroupId $emissionFactorGroupId): ?EmissionFactorGroup
    {
        return (isset($this->emissionFactorGroups[(string) $emissionFactorGroupId]) && false == $this->emissionFactorGroups[(string) $emissionFactorGroupId]->isRemoved())
            ? $this->emissionFactorGroups[(string) $emissionFactorGroupId]
            : null
        ;
    }

    public function findDefault(): ?EmissionFactorGroup
    {
        foreach ($this->emissionFactorGroups as $emissionFactorGroup) {
            if (true == $emissionFactorGroup->isRemoved()) {
                continue;
            }

            if (true == $emissionFactorGroup->isDefault()) {
                return $emissionFactorGroup;
            }
        }

        return null;
    }

    /** @return array<int, EmissionFactorGroup> */
    public function findByFilter(EmissionFactorGroupFilter $filter): array
    {
        $results = [];

        if (0 === count($this->emissionFactorGroups)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->emissionFactorGroups as $emissionFactorGroup) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $emissionFactorGroup->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $emissionFactorGroup->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $emissionFactorGroup;
        }

        return $results;
    }

    public function totalResultsByFilter(EmissionFactorGroupFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
