<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event;

use App\EventOperations\Domain\Model\Event\Event;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Model\Geo\GeoPoint;

/**
 * Resolves the geographic point of the venue: the coordinates explicitly set on the
 * event take precedence over the ones stored in the property of the event.
 */
final class EventVenueLocator
{
    public function __construct(private readonly PropertyRepository $propertyRepository) {}

    public function locate(?Event $event): ?GeoPoint
    {
        if (null === $event) {
            return null;
        }

        $latitude = $event->venueLatitude();
        $longitude = $event->venueLongitude();

        if (null !== $latitude && null !== $longitude) {
            return new GeoPoint($latitude, $longitude);
        }

        $propertyId = $event->propertyId();

        if (null === $propertyId) {
            return null;
        }

        $property = $this->propertyRepository->find($propertyId);

        if (null === $property) {
            return null;
        }

        $propertyLatitude = $this->coordinate($property->info()->latitude());
        $propertyLongitude = $this->coordinate($property->info()->longitude());

        if (null === $propertyLatitude || null === $propertyLongitude) {
            return null;
        }

        return new GeoPoint($propertyLatitude, $propertyLongitude);
    }

    private function coordinate(?string $value): ?float
    {
        if (null === $value || !is_numeric(trim($value))) {
            return null;
        }

        return (float) trim($value);
    }
}
