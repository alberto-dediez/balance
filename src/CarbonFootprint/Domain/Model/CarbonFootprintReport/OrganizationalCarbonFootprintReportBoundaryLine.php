<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Property\Domain\Model\Property\OperatingRegime;

final readonly class OrganizationalCarbonFootprintReportBoundaryLine
{
    public function __construct(
        public string $propertyName,
        public OperatingRegime $regime,
        public bool $consolidated,
        public ?Source $scope3Source,
    ) {}
}
