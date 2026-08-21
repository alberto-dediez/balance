<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

enum TripCalculationScope: string
{
    case Transport = 'transport';

    case OvernightStay = 'overnight_stay';

    case Both = 'both';

    public function includesTransport(): bool
    {
        return self::Both === $this || self::Transport === $this;
    }

    public function includesOvernightStay(): bool
    {
        return self::Both === $this || self::OvernightStay === $this;
    }

    public function asksForBoth(): bool
    {
        return self::Both === $this;
    }
}
