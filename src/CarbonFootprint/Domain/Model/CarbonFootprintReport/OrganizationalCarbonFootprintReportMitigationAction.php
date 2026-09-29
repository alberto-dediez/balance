<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class OrganizationalCarbonFootprintReportMitigationAction
{
    /** @param list<array{scope: Scope, source: Source, itemName: string, reduction: float}> $effects */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $baseYear,
        public readonly int $targetYear,
        public readonly array $effects,
    ) {}
}
