<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;

final readonly class OrganizationalCarbonFootprintReportEmployeeSurvey
{
    public function __construct(
        public ?int $totalEmployees,
        public ?int $answeredCount,
        public ?ConfidenceLevel $confidenceLevel,
        public ?float $marginOfError,
    ) {}

    public static function fromParticipation(OrganizationalEmployeeParticipation $participation): self
    {
        return new self(
            totalEmployees: $participation->totalEmployees(),
            answeredCount: $participation->answeredCount(),
            confidenceLevel: $participation->confidenceLevel(),
            marginOfError: $participation->marginOfError(),
        );
    }
}
