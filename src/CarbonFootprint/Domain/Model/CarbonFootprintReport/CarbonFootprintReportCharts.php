<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final class CarbonFootprintReportCharts
{
    /** @param array<string, string> $images */
    public function __construct(public readonly array $images) {}

    public function get(string $key): ?string
    {
        return $this->images[$key] ?? null;
    }
}
