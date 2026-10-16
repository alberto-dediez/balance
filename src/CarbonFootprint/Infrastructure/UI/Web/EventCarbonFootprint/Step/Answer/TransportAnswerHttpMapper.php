<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;

final class TransportAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::Transport;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);

        if (null === $itemId) {
            return null;
        }

        $weight = $this->parseNonNegativeFloat($row, 'weight');
        $weightUnit = Unit::tryFrom((string) ($row['weightUnit'] ?? ''));
        $distanceKm = $this->parseNonNegativeFloat($row, 'distanceKm');

        if (null !== $weight && null !== $weightUnit && null !== $distanceKm && Magnitude::Mass === $weightUnit->magnitude()) {
            return TransportAnswer::fromWeightAndDistance(
                transportId: new TransportId($itemId),
                weight: new Quantity($weight, $weightUnit),
                distanceKm: $distanceKm,
                existingActivityId: $this->parseActivityId($row),
                source: $this->parseSource($row),
            );
        }

        $quantity = $this->parseQuantity($row);

        if (null === $quantity) {
            return null;
        }

        return new TransportAnswer(
            transportId: new TransportId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
        );
    }

    /** @param array<array-key, mixed> $row */
    private function parseNonNegativeFloat(array $row, string $field): ?float
    {
        $value = str_replace(',', '.', trim((string) ($row[$field] ?? '')));

        if ('' === $value || !is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        return $value >= 0 ? $value : null;
    }
}
