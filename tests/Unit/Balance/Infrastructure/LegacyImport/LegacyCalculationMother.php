<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyCalculation;
use App\Balance\Infrastructure\LegacyImport\LegacyPlace;

class LegacyCalculationMother
{
    public static function from(
        ?string $referralSlug,
        string $transport = 'plane',
        bool $withOrigin = true,
        float $emissionsTotalGrams = 166809.7,
        string $createdAt = '2024-08-29 14:33:50',
        ?string $id = null,
    ): LegacyCalculation {
        return new LegacyCalculation(
            id: $id ?? 'calc-' . bin2hex(random_bytes(4)),
            referralSlug: $referralSlug,
            transport: $transport,
            nights: 2,
            persons: 3,
            roundTrip: false,
            distanceMeters: $withOrigin ? 403278.5 : 0.0,
            emissionsTotalGrams: $emissionsTotalGrams,
            origin: $withOrigin ? new LegacyPlace('place-madrid', 'Madrid, Spain', 40.48, -3.56) : null,
            destination: new LegacyPlace('place-sevilla', 'Sevilla, Spain', 37.38, -5.98),
            createdAt: new \DateTimeImmutable($createdAt),
        );
    }
}
