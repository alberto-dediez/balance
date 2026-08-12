<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;

final class TripLegsResolver
{
    private const float UNKNOWN_MODE_DETOUR_FACTOR = 1.25;

    public function __construct(
        private readonly RouteDistanceCalculator $routeDistanceCalculator,
        private readonly int $maxLegs = 16,
        private readonly float $minDistanceKm = 0.1,
    ) {}

    /**
     * @param list<TripWaypoint>  $waypoints
     * @param list<TripLegChoice> $choices
     *
     * @return list<ResolvedLeg>
     */
    public function resolve(LegKind $kind, array $waypoints, array $choices): array
    {
        if ([] === $choices || count($waypoints) < 2) {
            return [];
        }

        $waypoints = array_slice($waypoints, 0, $this->maxLegs + 1);
        $choices = array_slice($choices, 0, $this->maxLegs);

        $jumps = count($waypoints) - 1;

        if (count($choices) > $jumps) {
            $choices = array_slice($choices, 0, $jumps);
        }

        $legs = [];

        foreach ($choices as $index => $choice) {
            $origin = $waypoints[$index];
            $destination = $this->destinationFor($waypoints, $choices, $index);

            [$distanceKm, $distanceSource] = $this->distanceFor($origin, $destination, $choice);

            $legs[] = new ResolvedLeg(
                position: $index + 1,
                kind: $kind,
                mobilityId: $choice->mobilityId,
                vehicleOccupants: $choice->vehicleOccupants,
                origin: $origin,
                destination: $destination,
                distanceKm: $distanceKm,
                distanceSource: $distanceSource,
            );
        }

        return $legs;
    }

    /**
     * @param list<TripWaypoint>  $waypoints
     * @param list<TripLegChoice> $choices
     */
    private function destinationFor(array $waypoints, array $choices, int $index): TripWaypoint
    {
        if ($index === count($choices) - 1) {
            return $waypoints[count($waypoints) - 1];
        }

        return $waypoints[$index + 1];
    }

    /** @return array{0: float|null, 1: DistanceSource} */
    private function distanceFor(TripWaypoint $origin, TripWaypoint $destination, TripLegChoice $choice): array
    {
        if (null !== $origin->point && null !== $destination->point && null !== $choice->travelMode) {
            $routeDistance = $this->routeDistanceCalculator->calculate($origin->point, $destination->point, $choice->travelMode);

            if (null !== $routeDistance) {
                return [max($routeDistance->kilometers, $this->minDistanceKm), $routeDistance->source];
            }
        }

        if (null !== $choice->manualDistanceKm) {
            return [max($choice->manualDistanceKm, $this->minDistanceKm), DistanceSource::Manual];
        }

        if (null !== $origin->point && null !== $destination->point) {
            $detourFactor = $choice->travelMode?->detourFactor() ?? self::UNKNOWN_MODE_DETOUR_FACTOR;
            $kilometers = $origin->point->greatCircleDistanceKm($destination->point) * $detourFactor;

            return [max($kilometers, $this->minDistanceKm), DistanceSource::GreatCircle];
        }

        return [null, DistanceSource::Manual];
    }
}
