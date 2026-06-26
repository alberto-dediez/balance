<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

interface MachineryFuelRepository
{
    public function add(MachineryFuel $machineryFuel): void;

    public function remove(MachineryFuel $machineryFuel): void;

    public function find(MachineryFuelId $machineryFuelId): ?MachineryFuel;

    public function findOneByName(string $name): ?MachineryFuel;

    /** @return array<MachineryFuel> */
    public function findByFilter(MachineryFuelFilter $filter): array;

    public function totalResultsByFilter(MachineryFuelFilter $filter): int;
}
