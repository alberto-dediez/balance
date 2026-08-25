<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Map;

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
