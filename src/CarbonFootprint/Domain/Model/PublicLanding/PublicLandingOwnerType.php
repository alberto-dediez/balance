<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

enum PublicLandingOwnerType: string
{
    case Event = 'event';

    case Property = 'property';

    public function isEvent(): bool
    {
        return self::Event === $this;
    }

    public function isProperty(): bool
    {
        return self::Property === $this;
    }
}
