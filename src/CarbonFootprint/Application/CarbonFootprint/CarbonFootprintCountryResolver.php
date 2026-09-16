<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;

final class CarbonFootprintCountryResolver
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function resolve(CarbonFootprint $carbonFootprint, ?PropertyId $propertyId = null): ?CountryId
    {
        return match (true) {
            $carbonFootprint instanceof EventCarbonFootprint => $this->resolveForEvent($carbonFootprint),
            $carbonFootprint instanceof PropertyCarbonFootprint => $this->countryOf($carbonFootprint->propertyId()),
            $carbonFootprint instanceof CompanyCarbonFootprint => $this->countryOf($propertyId),
            default => null,
        };
    }

    private function resolveForEvent(EventCarbonFootprint $eventCarbonFootprint): ?CountryId
    {
        $eventId = $eventCarbonFootprint->eventId();

        if (null === $eventId) {
            return null;
        }

        $propertyId = $this->eventRepository->find($eventId)?->propertyId();

        if (null === $propertyId) {
            return null;
        }

        return $this->countryOf($propertyId);
    }

    private function countryOf(?PropertyId $propertyId): ?CountryId
    {
        if (null === $propertyId) {
            return null;
        }

        return $this->propertyRepository->find($propertyId)?->info()->countryId();
    }
}
