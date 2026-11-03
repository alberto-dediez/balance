<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions;

use App\Shared\Application\CommandBus\CommandResponse;

final readonly class DownloadJustifiedExclusionsDocumentResponse implements CommandResponse
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}
}
