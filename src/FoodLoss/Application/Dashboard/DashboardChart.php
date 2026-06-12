<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

final class DashboardChart
{
    /**
     * @param list<string>    $labels
     * @param list<int|float> $values
     * @param list<string>    $colors
     */
    public function __construct(public array $labels = [], public array $values = [], public array $colors = []) {}

    /**
     * @param array<string, int|float> $counts
     * @param list<string>             $colors
     */
    public static function fromCounts(array $counts, array $colors = []): self
    {
        return new self(
            labels: array_map(strval(...), array_keys($counts)),
            values: array_values($counts),
            colors: $colors,
        );
    }
}
