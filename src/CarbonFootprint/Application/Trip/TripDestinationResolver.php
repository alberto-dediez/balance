<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationNotAllowedException;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Application\Place\CountryRegionCode;
use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class TripDestinationResolver
{
    public const string PROPERTY_REFERENCE_PREFIX = 'property:';

    public function __construct(
        private readonly PlaceFinder $placeFinder,
        private readonly TripDestinationCandidates $tripDestinationCandidates,
        private readonly CountryRepository $countryRepository,
        private readonly PropertyLocator $propertyLocator,
        private readonly CountryRegionCode $countryRegionCode,
    ) {}

    public function resolve(TripCalculationLink $tripCalculationLink, string $reference): TripWaypoint
    {
        $reference = trim($reference);

        if ('' === $reference) {
            throw new \InvalidArgumentException('Debes indicar el destino de tu viaje.');
        }

        if ($tripCalculationLink->destinationMode()->usesProperties()) {
            return $this->resolveProperty($tripCalculationLink, $reference);
        }

        $place = $this->placeFinder->resolve($reference, LocaleGetter::locale());

        if (null === $place) {
            if (TripDestinationMode::Free === $tripCalculationLink->destinationMode()) {
                return new TripWaypoint(reference: $reference);
            }

            throw new TripDestinationNotAllowedException();
        }

        if (!$this->isAllowed($tripCalculationLink, $place)) {
            throw new TripDestinationNotAllowedException();
        }

        return new TripWaypoint(reference: $place->reference, label: $place->label, point: $place->point);
    }

    private function resolveProperty(TripCalculationLink $tripCalculationLink, string $reference): TripWaypoint
    {
        if (!str_starts_with($reference, self::PROPERTY_REFERENCE_PREFIX)) {
            throw new TripDestinationNotAllowedException();
        }

        $propertyId = substr($reference, strlen(self::PROPERTY_REFERENCE_PREFIX));

        foreach ($this->tripDestinationCandidates->allowedFor($tripCalculationLink) as $property) {
            if ((string) $property->id() === $propertyId) {
                return $this->propertyWaypoint($property);
            }
        }

        throw new TripDestinationNotAllowedException();
    }

    private function propertyWaypoint(Property $property): TripWaypoint
    {
        return new TripWaypoint(
            reference: self::PROPERTY_REFERENCE_PREFIX . $property->id(),
            label: $property->name(),
            point: $this->propertyLocator->locate($property),
        );
    }

    private function isAllowed(TripCalculationLink $tripCalculationLink, GeoPlace $place): bool
    {
        return match ($tripCalculationLink->destinationMode()) {
            TripDestinationMode::Countries => null !== $place->countryCode
                && in_array($this->countryRegionCode->alpha2($place->countryCode), $this->allowedRegionCodes($tripCalculationLink), true),
            TripDestinationMode::Region => $this->isInsideRegion($tripCalculationLink, $place),
            default => true,
        };
    }

    /** @return list<string> */
    private function allowedRegionCodes(TripCalculationLink $tripCalculationLink): array
    {
        $regionCodes = [];

        foreach ($tripCalculationLink->destinationCountryIds() as $countryId) {
            $iso = $this->countryRepository->find($countryId)?->iso();
            $regionCode = null !== $iso ? $this->countryRegionCode->alpha2($iso) : null;

            if (null !== $regionCode) {
                $regionCodes[] = $regionCode;
            }
        }

        return $regionCodes;
    }

    private function isInsideRegion(TripCalculationLink $tripCalculationLink, GeoPlace $place): bool
    {
        $bounds = $tripCalculationLink->destinationRegionBounds();

        if (null === $bounds || !$bounds->contains($place->point)) {
            return false;
        }

        $regionReference = $tripCalculationLink->destinationRegionReference();
        $region = null !== $regionReference ? $this->placeFinder->resolve($regionReference, LocaleGetter::locale()) : null;

        if (null === $region?->name) {
            return true;
        }

        $areaNames = array_map(static fn(string $name): string => mb_strtolower($name), $place->areaNames);

        return in_array(mb_strtolower($region->name), $areaNames, true);
    }
}
