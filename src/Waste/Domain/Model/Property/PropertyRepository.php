<?php

namespace App\Waste\Domain\Model\Property;

use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;

interface PropertyRepository
{
    public function add(Property $account): void;

    public function remove(Property $account): void;

    public function find(PropertyId $accountId): ?Property;

    public function findByName(string $name): ?Property;

    public function findByFilter(PropertyFilter $filter): array;

    public function totalResultsByFilter(PropertyFilter $filter): int;
}