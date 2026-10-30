<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

enum DataQuality: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';

    public static function worstOf(self ...$qualities): self
    {
        $worst = self::High;

        foreach ($qualities as $quality) {
            if ($quality->rank() > $worst->rank()) {
                $worst = $quality;
            }
        }

        return $worst;
    }

    public function label(): string
    {
        return match ($this) {
            self::High => 'Alta',
            self::Medium => 'Media',
            self::Low => 'Baja',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }
}
