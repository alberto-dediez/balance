<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Chart;

interface ApexChartClient
{
    /** @param array<string, mixed> $options */
    public function generate(array $options): string;
}
