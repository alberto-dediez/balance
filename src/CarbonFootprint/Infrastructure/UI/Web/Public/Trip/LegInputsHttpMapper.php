<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\Trip;

use App\CarbonFootprint\Application\Trip\LegInput;

/** Maps the repeatable legs of a trip block from their HTTP payload. */
final class LegInputsHttpMapper
{
    /**
     * @param array<mixed> $block
     *
     * @return list<array<string, mixed>>
     */
    public static function toSessionData(array $block): array
    {
        /** @var array<mixed> $legs */
        $legs = is_array($block['legs'] ?? null) ? $block['legs'] : [];

        $data = [];

        foreach ($legs as $leg) {
            if (!is_array($leg) || empty($leg['mobilityId'])) {
                continue;
            }

            $data[] = [
                'mobilityId' => (string) $leg['mobilityId'],
                'vehicleOccupants' => !empty($leg['vehicleOccupants']) ? (int) $leg['vehicleOccupants'] : null,
                'destinationPlaceReference' => !empty($leg['destinationPlaceReference']) ? (string) $leg['destinationPlaceReference'] : null,
                'manualDistanceKm' => !empty($leg['manualDistanceKm'])
                    ? (float) str_replace(',', '.', (string) $leg['manualDistanceKm'])
                    : null,
            ];
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<LegInput>
     */
    public static function toLegInputs(array $data): array
    {
        $legInputs = [];

        foreach ($data as $leg) {
            if (!is_array($leg) || empty($leg['mobilityId'])) {
                continue;
            }

            $legInputs[] = new LegInput(
                mobilityId: (string) $leg['mobilityId'],
                vehicleOccupants: null !== ($leg['vehicleOccupants'] ?? null) ? (int) $leg['vehicleOccupants'] : null,
                destinationPlaceReference: null !== ($leg['destinationPlaceReference'] ?? null) ? (string) $leg['destinationPlaceReference'] : null,
                manualDistanceKm: null !== ($leg['manualDistanceKm'] ?? null) ? (float) $leg['manualDistanceKm'] : null,
            );
        }

        return $legInputs;
    }
}
