<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Shared\Domain\Model\Geo\PlaceKind;

enum TripDestinationPlaceKind: string
{
    case City = 'city';

    case Lodging = 'lodging';

    case Any = 'any';

    public function toPlaceKind(): PlaceKind
    {
        return match ($this) {
            self::City => PlaceKind::City,
            self::Lodging => PlaceKind::Lodging,
            self::Any => PlaceKind::Any,
        };
    }
}
