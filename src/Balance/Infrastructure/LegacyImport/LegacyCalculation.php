<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyCalculation
{
    public function __construct(
        public string $id,
        public ?string $referralSlug,
        public string $transport,
        public int $nights,
        public int $persons,
        public bool $roundTrip,
        public float $distanceMeters,
        public float $emissionsTotalGrams,
        public ?LegacyPlace $origin,
        public ?LegacyPlace $destination,
        public \DateTimeImmutable $createdAt,
    ) {}
}
