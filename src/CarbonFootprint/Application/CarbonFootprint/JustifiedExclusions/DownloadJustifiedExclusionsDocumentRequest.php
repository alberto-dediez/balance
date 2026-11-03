<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\JustifiedExclusions;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Shared\Application\CommandBus\Command;

final readonly class DownloadJustifiedExclusionsDocumentRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
