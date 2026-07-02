<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

final class ActivityRowParser
{
    /**
     * @param array<array-key, mixed> $rows
     *
     * @return list<ActivityRow>
     */
    public function parse(array $rows, ActivityType $type, bool $withCondition = false, bool $hasSecondaryItemId = false): array
    {
        $parsed = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemId = trim((string) ($row['itemId'] ?? ''));
            $unit = Unit::tryFrom((string) ($row['unit'] ?? ''));
            $quantity = (float) str_replace(',', '.', (string) ($row['quantity'] ?? ''));
            $activityId = trim((string) ($row['activityId'] ?? ''));
            $secondaryItemId = $hasSecondaryItemId ? trim((string) ($row['itemId2'] ?? '')) : '';
            $condition = $withCondition
                ? (ItemCondition::tryFrom((string) ($row['condition'] ?? '')) ?? ItemCondition::New)
                : ItemCondition::New;

            if ('' === $itemId || null === $unit || $quantity <= 0 || ($hasSecondaryItemId && '' === $secondaryItemId)) {
                continue;
            }

            $parsed[] = new ActivityRow(
                activityType: $type,
                itemId: $itemId,
                condition: $condition,
                quantity: new Quantity($quantity, $unit),
                activityId: '' !== $activityId ? new ActivityId($activityId) : null,
                secondaryItemId: $hasSecondaryItemId ? $secondaryItemId : null,
            );
        }

        return $parsed;
    }
}
