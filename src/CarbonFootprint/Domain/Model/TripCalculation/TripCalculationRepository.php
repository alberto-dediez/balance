<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;

interface TripCalculationRepository
{
    public function add(TripCalculation $tripCalculation): void;

    public function find(TripCalculationId $tripCalculationId): ?TripCalculation;

    /** @return list<TripCalculation> */
    public function findByFilter(TripCalculationFilter $filter): array;

    public function totalResultsByFilter(TripCalculationFilter $filter): int;

    /** @return list<TripCalculation> */
    public function findByLinkId(TripCalculationLinkId $tripCalculationLinkId): array;
}
