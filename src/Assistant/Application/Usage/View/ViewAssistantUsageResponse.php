<?php

declare(strict_types=1);

namespace App\Assistant\Application\Usage\View;

class ViewAssistantUsageResponse
{
    /**
     * @param array<string, AssistantUsageTotals> $days
     * @param array<string, AssistantUsageTotals> $users
     * @param array<string, AssistantUsageTotals> $models
     */
    public function __construct(
        public AssistantUsageTotals $totals,
        public array $days,
        public array $users,
        public array $models,
    ) {}
}
