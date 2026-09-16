<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPropertyCarbonFootprintRepository implements PropertyCarbonFootprintRepository
{
    /** @param array<string, PropertyCarbonFootprint> $propertyCarbonFootprints */
    public function __construct(
        private array $propertyCarbonFootprints = [],
    ) {}

    public function add(PropertyCarbonFootprint $propertyCarbonFootprint): void
    {
        if (isset($this->propertyCarbonFootprints[(string) $propertyCarbonFootprint->id()])) {
            return;
        }

        $this->propertyCarbonFootprints[(string) $propertyCarbonFootprint->id()] = $propertyCarbonFootprint;
    }

    public function remove(PropertyCarbonFootprint $propertyCarbonFootprint): void
    {
        if (!isset($this->propertyCarbonFootprints[(string) $propertyCarbonFootprint->id()])) {
            return;
        }

        unset($this->propertyCarbonFootprints[(string) $propertyCarbonFootprint->id()]);
    }

    public function find(PropertyCarbonFootprintId $propertyCarbonFootprintId): ?PropertyCarbonFootprint
    {
        return (isset($this->propertyCarbonFootprints[(string) $propertyCarbonFootprintId]) && false == $this->propertyCarbonFootprints[(string) $propertyCarbonFootprintId]->isRemoved())
            ? $this->propertyCarbonFootprints[(string) $propertyCarbonFootprintId]
            : null
        ;
    }

    public function findByPropertyIdAndYear(PropertyId $propertyId, int $year): ?PropertyCarbonFootprint
    {
        foreach ($this->propertyCarbonFootprints as $propertyCarbonFootprint) {
            if (true == $propertyCarbonFootprint->isRemoved()) {
                continue;
            }

            $subjectPropertyId = $propertyCarbonFootprint->propertyId();

            if (null !== $subjectPropertyId && $subjectPropertyId->equals($propertyId) && $propertyCarbonFootprint->year() === $year) {
                return $propertyCarbonFootprint;
            }
        }

        return null;
    }

    /** @return array<PropertyCarbonFootprint> */
    public function findByPropertyId(PropertyId $propertyId): array
    {
        $results = [];

        foreach ($this->propertyCarbonFootprints as $propertyCarbonFootprint) {
            if (true == $propertyCarbonFootprint->isRemoved()) {
                continue;
            }

            $subjectPropertyId = $propertyCarbonFootprint->propertyId();

            if (null !== $subjectPropertyId && $subjectPropertyId->equals($propertyId)) {
                $results[] = $propertyCarbonFootprint;
            }
        }

        return $results;
    }

    public function findByActivityId(ActivityId $activityId): ?PropertyCarbonFootprint
    {
        foreach ($this->propertyCarbonFootprints as $propertyCarbonFootprint) {
            if (true == $propertyCarbonFootprint->isRemoved()) {
                continue;
            }

            foreach ($propertyCarbonFootprint->activityReferences() as $reference) {
                if ($reference->activityId->equals($activityId)) {
                    return $propertyCarbonFootprint;
                }
            }
        }

        return null;
    }

    /** @return array<int, PropertyCarbonFootprint> */
    public function findByFilter(PropertyCarbonFootprintFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->propertyCarbonFootprints as $propertyCarbonFootprint) {
            if (true == $propertyCarbonFootprint->isRemoved()) {
                continue;
            }

            if ($filter->propertyId instanceof PropertyId && (null === $propertyCarbonFootprint->propertyId() || !$filter->propertyId->equals($propertyCarbonFootprint->propertyId()))) {
                continue;
            }

            if (null !== $filter->year && $propertyCarbonFootprint->year() !== $filter->year) {
                continue;
            }

            if ($filter->status instanceof OrganizationalCarbonFootprintStatus && $propertyCarbonFootprint->status() !== $filter->status) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $propertyCarbonFootprint->accountId() || !$filter->accountId->equals($propertyCarbonFootprint->accountId()))) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $propertyCarbonFootprint;
        }

        return $results;
    }

    public function totalResultsByFilter(PropertyCarbonFootprintFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
