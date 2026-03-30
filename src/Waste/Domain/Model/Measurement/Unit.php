<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use Symfony\Contracts\Translation\TranslatorInterface;

enum Unit: string
{
    public const LITERS_IN_A_M3 = 1000;

    case CubicMeter = 'm3';

    case Liter = 'l';

    case Kilogram = 'kg';

    case Percentage = '%';

    public function calculateLoadInKilograms(float $load, float $volume, float $density): float
    {
        return match ($this) {
            self::Kilogram => $load,
            self::Liter => $load * $density,
            self::CubicMeter => $load * $density * self::LITERS_IN_A_M3,
            self::Percentage => ($load / 100) * $volume * $density,
        };
    }

    public function label(TranslatorInterface $translator): string
    {
        return $translator->trans($this->value, [], 'unit');
    }
}
