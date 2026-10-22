<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class OrganizationalCarbonFootprintReportExclusionLine
{
    public function __construct(
        public readonly Scope $scope,
        public readonly ?Source $source,
        public readonly ExclusionReason $reason,
        public readonly ?string $comment = null,
    ) {}
}
