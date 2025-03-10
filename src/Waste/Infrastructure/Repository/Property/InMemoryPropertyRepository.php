<?php

namespace App\Waste\Infrastructure\Repository\Property;

use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;
use App\Waste\Domain\Model\Property\PropertyRepository;
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

    public function findByFilter(PropertyFilter $filter): array
    {
        $results = [];

        if (count($this->properties) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->properties as $property) {
            if (!empty($filter->name) && $filter->name != $property->name()) {
                continue;
            }

            if (null !== $filter->account && (null == $property->accountId() || false == $filter->account->id()->equals($property->accountId()))) {
                continue;
            }

            if (true == $property->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit) {
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