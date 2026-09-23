<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final class EventCarbonFootprintReportCompensationProjectLine
{
    public function __construct(
        public readonly EventCarbonFootprintReportCompensationScope $scope,
        public readonly string $projectName,
        public readonly ?string $projectLocation,
        public readonly ?string $projectRegistryId,
        public readonly bool $isCompensationProject,
        public readonly int $contributions,
        public readonly float $tonnes,
    ) {}
}
