<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\List;

class ListTrainingAttendancesResponse
{
    /** @param array<TrainingAttendanceSummary> $summaries */
    public function __construct(
        public array $summaries = [],
    ) {}
}
