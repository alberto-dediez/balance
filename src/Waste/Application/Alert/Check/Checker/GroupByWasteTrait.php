<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check\Checker;

use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Waste\WasteId;

trait GroupByWasteTrait
{
    /**
     * @param DailyWaste[] $dailyWastes
     *
     * @return array<string, array{WasteId, DailyWaste[]}>
     */
    private function groupByWaste(array $dailyWastes): array
    {
        $grouped = [];

        foreach ($dailyWastes as $dw) {
            $wasteId = $dw->wasteId();

            if (null === $wasteId) {
                continue;
            }

            $key = (string) $wasteId;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [$wasteId, []];
            }

            $grouped[$key][1][] = $dw;
        }

        return $grouped;
    }

    /**
     * @param DailyWaste[] $dailyWastes
     */
    private function findForDay(array $dailyWastes, \DateTimeImmutable $day): ?DailyWaste
    {
        foreach ($dailyWastes as $dw) {
            if ($dw->day()->format('Y-m-d') === $day->format('Y-m-d')) {
                return $dw;
            }
        }

        return null;
    }
}
