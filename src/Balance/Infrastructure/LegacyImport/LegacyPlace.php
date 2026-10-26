<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyPlace
{
    public function __construct(
        public ?string $placeId,
        public ?string $formattedAddress,
        public float $latitude,
        public float $longitude,
    ) {}
}
