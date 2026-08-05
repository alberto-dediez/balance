<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;

/**
 * Turns the chain of points of a trip block into its legs, resolving the one way
 * distance of each of them: real route first, the kilometers declared by the attendee
 * next and the great circle between both points as the last resort. It never throws.
 */
final class AttendeeTripLegsResolver
{
    /** Detour applied to the great circle distance when the means of transport has no travel mode. */
    private const float UNKNOWN_MODE_DETOUR_FACTOR = 1.25;

    public function __construct(
        private readonly RouteDistanceCalculator $routeDistanceCalculator,
        private readonly int $maxLegs = 4,
        private readonly float $minDistanceKm = 0.1,
    ) {}

    /**
     * @param list<TripWaypoint>  $waypoints N points: origin, intermediate stops and destination
     * @param list<TripLegChoice> $choices   N-1 choices, one per jump between consecutive points
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
     * The last choice always ends at the final point of the chain, so a chain with
     * more points than choices collapses its tail into the last leg.
     *
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

    /**
     * A leg the attendee declared always represents a real trip, so a distance that
     * collapses to zero (points inside the same block, venue and origin resolved to
     * the same coordinates) is floored instead of discarded. Two known points are
     * always enough to estimate it, even when the means of transport has no travel
     * mode to route it with.
     *
     * @return array{0: float|null, 1: DistanceSource}
     */
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
