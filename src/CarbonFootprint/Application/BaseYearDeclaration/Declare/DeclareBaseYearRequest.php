<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Declare;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Shared\Application\CommandBus\Command;

class DeclareBaseYearRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $subjectId,
        public ?int $year = null,
        public ?string $justification = null,
        public ?string $recalculationThresholdPercent = '5',
        public ?IntensityIndicator $primaryIndicator = null,
    ) {}
}
