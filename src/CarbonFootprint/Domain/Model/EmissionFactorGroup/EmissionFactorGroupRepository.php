<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

interface EmissionFactorGroupRepository
{
    public function add(EmissionFactorGroup $emissionFactorGroup): void;

    public function remove(EmissionFactorGroup $emissionFactorGroup): void;

    public function find(EmissionFactorGroupId $emissionFactorGroupId): ?EmissionFactorGroup;

    public function findOneByCode(string $code): ?EmissionFactorGroup;

    /** @return array<EmissionFactorGroup> */
    public function findByFilter(EmissionFactorGroupFilter $filter): array;

    public function totalResultsByFilter(EmissionFactorGroupFilter $filter): int;
}
