<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyRepository;

final class EventCarbonFootprintCountryResolver
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function resolve(EventCarbonFootprint $eventCarbonFootprint): ?CountryId
    {
        $eventId = $eventCarbonFootprint->eventId();

        if (null === $eventId) {
            return null;
        }

        $propertyId = $this->eventRepository->find($eventId)?->propertyId();

        if (null === $propertyId) {
            return null;
        }

        return $this->propertyRepository->find($propertyId)?->info()->countryId();
    }
}
