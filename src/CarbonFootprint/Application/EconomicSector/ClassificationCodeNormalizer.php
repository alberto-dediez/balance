<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector;

final class ClassificationCodeNormalizer
{
    public static function normalize(?string $code): ?string
    {
        $normalized = mb_strtoupper(trim((string) $code));

        return '' === $normalized ? null : $normalized;
    }
}
