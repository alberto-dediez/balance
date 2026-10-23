<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Map;

use App\CarbonFootprint\Application\Trip\OriginDestinationFlow;
use App\CarbonFootprint\Application\Trip\OriginFlow;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class TripFlowsMapBuilder
{
    /**
     * @param list<OriginFlow> $originFlows
     *
     * @return list<array{lat: float, lng: float, label: string, responses: int, travellers: int, km: float, co2eq: float}>
     */
    public function flows(array $originFlows): array
    {
        return array_map(
            static fn(OriginFlow $flow): array => [
                'lat' => $flow->latitude,
                'lng' => $flow->longitude,
                'label' => $flow->label,
                'responses' => $flow->responses,
                'travellers' => $flow->travellers,
                'km' => round($flow->km, 1),
                'co2eq' => round((float) $flow->emissions->co2eq(), 2),
            ],
            $originFlows,
        );
    }

    /**
     * @param list<OriginDestinationFlow> $originDestinationFlows
     *
     * @return list<array{lat: float, lng: float, label: string, destLat: float, destLng: float, destLabel: string, responses: int, travellers: int, km: float, co2eq: float}>
     */
    public function originDestinationFlows(array $originDestinationFlows): array
    {
        return array_map(
            static fn(OriginDestinationFlow $flow): array => [
                'lat' => $flow->originLatitude,
                'lng' => $flow->originLongitude,
                'label' => $flow->originLabel,
                'destLat' => $flow->destinationLatitude,
                'destLng' => $flow->destinationLongitude,
                'destLabel' => $flow->destinationLabel,
                'responses' => $flow->responses,
                'travellers' => $flow->travellers,
                'km' => round($flow->km, 1),
                'co2eq' => round((float) $flow->emissions->co2eq(), 2),
            ],
            $originDestinationFlows,
        );
    }

    /** @return array{lat: float, lng: float, label: string}|null */
    public function destination(?GeoPoint $destination, ?string $label): ?array
    {
        if (null === $destination) {
            return null;
        }

        return [
            'lat' => $destination->latitude,
            'lng' => $destination->longitude,
            'label' => $label ?? '',
        ];
    }
}
