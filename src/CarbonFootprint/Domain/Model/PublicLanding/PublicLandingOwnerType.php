<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

enum PublicLandingOwnerType: string
{
    case Event = 'event';

    case Property = 'property';

    case TripCalculationLink = 'trip_link';

    public function isEvent(): bool
    {
        return self::Event === $this;
    }

    public function isProperty(): bool
    {
        return self::Property === $this;
    }

    public function isTripCalculationLink(): bool
    {
        return self::TripCalculationLink === $this;
    }
}
