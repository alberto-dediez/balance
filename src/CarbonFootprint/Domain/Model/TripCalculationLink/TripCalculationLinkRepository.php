<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Property\PropertyId;

interface TripCalculationLinkRepository
{
    public function add(TripCalculationLink $tripCalculationLink): void;

    public function find(TripCalculationLinkId $tripCalculationLinkId): ?TripCalculationLink;

    public function findByPropertyId(PropertyId $propertyId): ?TripCalculationLink;

    public function findByToken(#[\SensitiveParameter] string $token): ?TripCalculationLink;
}
