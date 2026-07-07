<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Application\Activity\WasteContainerQuantityResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\WasteAnswer;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;

final class WasteAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function __construct(private readonly WasteContainerQuantityResolver $resolver) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Waste;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);

        if (null === $itemId) {
            return null;
        }

        if ('container' === ($row['mode'] ?? null)) {
            $containerCapacity = $this->parseContainerCapacity($row);
            $fillPercentage = $this->parseFillPercentage($row);

            if (null === $containerCapacity || null === $fillPercentage) {
                return null;
            }

            $wasteId = new WasteId($itemId);

            return new WasteAnswer(
                wasteId: $wasteId,
                quantity: $this->resolver->resolve($wasteId, $containerCapacity, $fillPercentage),
                existingActivityId: $this->parseActivityId($row),
                source: $this->parseSource($row),
                containerCapacity: $containerCapacity,
                fillPercentage: $fillPercentage,
            );
        }

        $quantity = $this->parseQuantity($row);

        if (null === $quantity) {
            return null;
        }

        return new WasteAnswer(
            wasteId: new WasteId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
        );
    }

    /** @param array<array-key, mixed> $row */
    private function parseContainerCapacity(array $row): ?Quantity
    {
        $unit = Unit::tryFrom((string) ($row['containerUnit'] ?? ''));
        $capacity = (float) str_replace(',', '.', (string) ($row['containerCapacity'] ?? ''));

        if (null === $unit || Magnitude::Volume !== $unit->magnitude() || $capacity <= 0) {
            return null;
        }

        return new Quantity($capacity, $unit);
    }

    /** @param array<array-key, mixed> $row */
    private function parseFillPercentage(array $row): ?float
    {
        $fillPercentage = (float) str_replace(',', '.', (string) ($row['fillPercentage'] ?? ''));

        if ($fillPercentage < 0.0 || $fillPercentage > 100.0) {
            return null;
        }

        return $fillPercentage;
    }
}
