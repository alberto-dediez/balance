<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class GeoPlace
{
    /** @param list<string> $areaNames */
    public function __construct(
        public string $reference,
        public string $label,
        public GeoPoint $point,
        public ?string $name = null,
        public ?GeoBounds $viewport = null,
        public array $areaNames = [],
        public ?string $countryCode = null,
    ) {}
}
