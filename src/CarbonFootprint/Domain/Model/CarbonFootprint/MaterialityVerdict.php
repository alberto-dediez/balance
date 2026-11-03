<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

enum MaterialityVerdict: string
{
    case Calculate = 'calculate';
    case Omit      = 'omit';

    public function style(): string
    {
        return match ($this) {
            self::Calculate => 'bg-success',
            self::Omit => 'bg-secondary',
        };
    }

    public function recommendsExclusion(): bool
    {
        return self::Omit === $this;
    }
}
