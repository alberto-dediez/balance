<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

interface ElectricityMarketerRepository
{
    public function add(ElectricityMarketer $electricityMarketer): void;

    public function remove(ElectricityMarketer $electricityMarketer): void;

    public function find(ElectricityMarketerId $electricityMarketerId): ?ElectricityMarketer;

    public function findOneByName(string $name): ?ElectricityMarketer;

    /** @return array<ElectricityMarketer> */
    public function findByFilter(ElectricityMarketerFilter $filter): array;

    public function totalResultsByFilter(ElectricityMarketerFilter $filter): int;
}
