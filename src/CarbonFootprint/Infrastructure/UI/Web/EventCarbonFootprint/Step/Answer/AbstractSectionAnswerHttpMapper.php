<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

abstract class AbstractSectionAnswerHttpMapper implements SectionAnswerHttpMapper
{
    /** @param array<array-key, mixed> $row */
    protected function parseItemId(array $row): ?string
    {
        $itemId = trim((string) ($row['itemId'] ?? ''));

        return '' !== $itemId ? $itemId : null;
    }

    /** @param array<array-key, mixed> $row */
    protected function parseSecondaryItemId(array $row): ?string
    {
        $secondaryItemId = trim((string) ($row['itemId2'] ?? ''));

        return '' !== $secondaryItemId ? $secondaryItemId : null;
    }

    /** @param array<array-key, mixed> $row */
    protected function parseQuantity(array $row): ?Quantity
    {
        $unit = Unit::tryFrom((string) ($row['unit'] ?? ''));
        $quantity = (float) str_replace(',', '.', (string) ($row['quantity'] ?? ''));

        if (null === $unit || $quantity <= 0) {
            return null;
        }

        return new Quantity($quantity, $unit);
    }

    /** @param array<array-key, mixed> $row */
    protected function parseActivityId(array $row): ?ActivityId
    {
        $activityId = trim((string) ($row['activityId'] ?? ''));

        return '' !== $activityId ? new ActivityId($activityId) : null;
    }

    /** @param array<array-key, mixed> $row */
    protected function parseCondition(array $row): ItemCondition
    {
        return ItemCondition::tryFrom((string) ($row['condition'] ?? '')) ?? ItemCondition::New;
    }
}
