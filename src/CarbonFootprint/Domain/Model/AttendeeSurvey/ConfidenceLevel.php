<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

enum ConfidenceLevel: int
{
    case Eighty          = 80;
    case EightyFive      = 85;
    case Ninety          = 90;
    case NinetyFive      = 95;
    case NinetyNine      = 99;

    public function zScore(): float
    {
        return match ($this) {
            self::Eighty     => 1.282,
            self::EightyFive => 1.440,
            self::Ninety     => 1.645,
            self::NinetyFive => 1.960,
            self::NinetyNine => 2.576,
        };
    }
}
