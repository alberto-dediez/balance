<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event;

use App\Shared\Domain\Model\Geo\GeoPlace;

/**
 * The outcome of resolving a place reference, ready to be applied to the event without any further failure.
 */
final readonly class EventVenueLocationChange
{
    private function __construct(
        public bool $changed,
        public ?string $reference,
        public ?string $label,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function none(): self
    {
        return new self(false, null, null, null, null);
    }

    public static function clear(): self
    {
        return new self(true, null, null, null, null);
    }

    public static function to(GeoPlace $place): self
    {
        return new self(
            true,
            $place->reference,
            $place->label,
            $place->point->latitude,
            $place->point->longitude,
        );
    }
}
