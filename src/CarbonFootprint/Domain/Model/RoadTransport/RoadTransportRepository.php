<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

interface RoadTransportRepository
{
    public function add(RoadTransport $roadTransport): void;

    public function remove(RoadTransport $roadTransport): void;

    public function find(RoadTransportId $roadTransportId): ?RoadTransport;

    public function findOneByName(string $name): ?RoadTransport;

    /** @return array<RoadTransport> */
    public function findByFilter(RoadTransportFilter $filter): array;

    public function totalResultsByFilter(RoadTransportFilter $filter): int;
}
