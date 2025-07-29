<?php

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyFilter;

interface PropertyRepository
{
    public function add(Property $property): void;

    public function remove(Property $property): void;

    public function find(PropertyId $propertyId): ?Property;

    public function findByName(string $name): ?Property;

    public function findByExternalId(ExternalId $externalId): ?Property;

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Property;

    public function findByGeoPosition(string $latitude, string $longitude): ?Property;

    public function findByFilter(PropertyFilter $filter): array;

    public function totalResultsByFilter(PropertyFilter $filter): int;
}