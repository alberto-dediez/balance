<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Geo;

final readonly class PlaceSearchArea
{
    public const int MAX_REGION_CODES = 15;

    /** @param list<string> $regionCodes */
    public function __construct(
        public array $regionCodes = [],
        public ?GeoBounds $bounds = null,
    ) {
        if (count($regionCodes) > self::MAX_REGION_CODES) {
            throw new \InvalidArgumentException(sprintf('A place search area admits up to %d region codes.', self::MAX_REGION_CODES));
        }
    }
}
