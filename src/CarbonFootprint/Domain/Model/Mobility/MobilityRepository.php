<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

interface MobilityRepository
{
    public function add(Mobility $mobility): void;

    public function remove(Mobility $mobility): void;

    public function find(MobilityId $mobilityId): ?Mobility;

    public function findOneByName(string $name): ?Mobility;

    /** @return array<Mobility> */
    public function findByFilter(MobilityFilter $filter): array;

    public function totalResultsByFilter(MobilityFilter $filter): int;
}
