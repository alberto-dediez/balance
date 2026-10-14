<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class GeoPlace
{
    public function __construct(
        public string $reference,
        public string $label,
        public GeoPoint $point,
    ) {}
}
