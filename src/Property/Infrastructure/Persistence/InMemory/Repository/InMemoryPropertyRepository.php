<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPropertyRepository implements PropertyRepository
{
    /** @param Property[] $properties */
    public function __construct(private array $properties = []) {}

    public function add(Property $property): void
    {
        if (isset($this->properties[(string) $property->id()])) {
            return;
        }

        $this->properties[(string) $property->id()] = $property;
    }

    public function remove(Property $property): void
    {
        if (!isset($this->properties[(string) $property->id()])) {
            return;
        }

        unset($this->properties[(string) $property->id()]);
    }

    public function find(PropertyId $propertyId): ?Property
    {
        return (isset($this->properties[(string) $propertyId]) && false == $this->properties[(string) $propertyId]->isRemoved())
            ? $this->properties[(string) $propertyId]
            : null
        ;
    }

    public function findByName(string $name): ?Property
    {
        if (0 === count($this->properties)) {
            return null;
        }

        foreach ($this->properties as $property) {
            if ($property->name() == $name) {
                return $this->find($property->id());
            }
        }

        return null;
    }

    public function findByExternalId(ExternalId $externalId): ?Property
    {
        if (0 === count($this->properties)) {
            return null;
        }

        foreach ($this->properties as $property) {
            if (null !== $property->externalId()->bioscore() && $property->externalId()->bioscore() == $externalId->bioscore()) {
                return $this->find($property->id());
            }

            if ($property->externalId()->restel() && $property->externalId()->restel() == $externalId->restel()) {
                return $this->find($property->id());
            }

            if ($property->externalId()->hotelbeds() && $property->externalId()->hotelbeds() == $externalId->hotelbeds()) {
                return $this->find($property->id());
            }
        }

        return null;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Property
    {
        if (0 === count($this->properties)) {
            return null;
        }

        $sourceId = $source->id($externalId);
        $attribute = $source->attribute();

        foreach ($this->properties as $property) {
            if ($property->externalId()->isEmpty()) {
                continue;
            }

            if ($property->externalId()->$attribute() === $sourceId) {
                return $this->find($property->id());
            }
        }

        return null;
    }

    /** @return Property[] */
    public function findByCompanyId(CompanyId $companyId): array
    {
        $results = [];

        foreach ($this->properties as $property) {
            if (true == $property->isRemoved()) {
                continue;
            }

            if (null !== $property->companyId() && $property->companyId()->equals($companyId)) {
                $results[] = $property;
            }
        }

        return $results;
    }

    public function findByGeoPosition(string $latitude, string $longitude): ?Property
    {
        if (0 === count($this->properties)) {
            return null;
        }

        foreach ($this->properties as $property) {
            if ($property->info()->latitude() == $latitude && $property->info()->longitude() == $longitude) {
                return $this->find($property->id());
            }
        }

        return null;
    }

    /** @return Property[] */
    public function findByFilter(PropertyFilter $filter): array
    {
        $results = [];

        if (0 === count($this->properties)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->properties as $property) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $property->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null == $property->accountId() || false === $filter->accountId->equals($property->accountId()))) {
                continue;
            }

            if ($filter->destinationId instanceof DestinationId && (null == $property->info()->destinationId() || false === $filter->destinationId->equals($property->info()->destinationId()))) {
                continue;
            }

            if ($filter->countryId instanceof CountryId && (null == $property->info()->countryId() || false === $filter->countryId->equals($property->info()->countryId()))) {
                continue;
            }

            if ($filter->operatingRegime instanceof OperatingRegime && $filter->operatingRegime !== $property->operatingRegime()) {
                continue;
            }

            if (true == $property->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $property;
        }

        return $results;
    }

    public function totalResultsByFilter(PropertyFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
