<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

interface OvernightStayRepository
{
    public function add(OvernightStay $overnightStay): void;

    public function remove(OvernightStay $overnightStay): void;

    public function find(OvernightStayId $overnightStayId): ?OvernightStay;

    /** @return array<OvernightStay> */
    public function findByFilter(OvernightStayFilter $filter): array;

    public function totalResultsByFilter(OvernightStayFilter $filter): int;
}
