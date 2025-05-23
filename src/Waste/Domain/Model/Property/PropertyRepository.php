<?php

namespace App\Waste\Domain\Model\Property;

use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;

interface PropertyRepository
{
    public function add(Property $property): void;

    public function remove(Property $property): void;

    public function find(PropertyId $propertyId): ?Property;

    public function findByName(string $name): ?Property;

    public function findByFilter(PropertyFilter $filter): array;

    public function totalResultsByFilter(PropertyFilter $filter): int;
}