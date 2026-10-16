<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

interface TransportRepository
{
    public function add(Transport $transport): void;

    public function remove(Transport $transport): void;

    public function find(TransportId $transportId): ?Transport;

    /** @return array<Transport> */
    public function findByFilter(TransportFilter $filter): array;

    public function totalResultsByFilter(TransportFilter $filter): int;
}
