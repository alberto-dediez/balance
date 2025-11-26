<?php

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPropertyRepository implements PropertyRepository
{
    public function __construct(private array $properties = [])
    {

    }

    public function add(Property $property): void
    {
        if (isset($this->properties[(string) $property->id()])) {
            return ;
        }

        $this->properties[(string) $property->id()] = $property;
    }

    public function remove(Property $property): void
    {
        if (!isset($this->properties[(string) $property->id()])) {
            return ;
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
        if (count($this->properties) == 0) {
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
        if (count($this->properties) == 0) {
            return null;
        }

        foreach ($this->properties as $property) {
            if ($property->externalId()->bioscore() !== null && $property->externalId()->bioscore() == $externalId->bioscore()) {
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
        if (count($this->properties) == 0) {
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

    public function findByGeoPosition(string $latitude, string $longitude): ?Property
    {
        if (count($this->properties) == 0) {
            return null;
        }

        foreach ($this->properties as $property) {
            if ($property->info()->latitude() == $latitude && $property->info()->longitude() == $longitude) {
                return $this->find($property->id());
            }
        }

        return null;
    }

    public function findByFilter(PropertyFilter $filter): array
    {
        $results = [];

        if (count($this->properties) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->properties as $property) {
            if (
                !empty($filter->name)
                && false === strpos(strtolower($property->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->accountId && (null == $property->accountId() || false == $filter->accountId->equals($property->accountId()))) {
                continue;
            }

            if (null !== $filter->destinationId && (null == $property->info()?->destinationId() || false == $filter->destinationId->equals($property->info()->destinationId()))) {
                continue;
            }

            if (null !== $filter->countryId && (null == $property->info()?->countryId() || false == $filter->countryId->equals($property->info()->countryId()))) {
                continue;
            }

            if (true == $property->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

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

        return count($this->findByFilter($filter));
    }
}
