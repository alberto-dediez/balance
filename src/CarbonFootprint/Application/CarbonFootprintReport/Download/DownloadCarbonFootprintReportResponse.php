<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Download;

final class DownloadCarbonFootprintReportResponse
{
    public function __construct(
        public readonly mixed $stream,
        public readonly string $filename,
    ) {}
}
