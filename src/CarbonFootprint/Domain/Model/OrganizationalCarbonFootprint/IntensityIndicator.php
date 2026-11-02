<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

enum IntensityIndicator: string
{
    case PerOccupiedRoom       = 'per_occupied_room';
    case PerStay               = 'per_stay';
    case PerSquareMeter        = 'per_square_meter';
    case PerCover              = 'per_cover';
    case PerEmployee           = 'per_employee';
    case PerMillionEuroRevenue = 'per_million_euro_revenue';
    case PerCustomer           = 'per_customer';

    public function denominatorUnitKey(): string
    {
        return $this->value . '_unit';
    }
}
