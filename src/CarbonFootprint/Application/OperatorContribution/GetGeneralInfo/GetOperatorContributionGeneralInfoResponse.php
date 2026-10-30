<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetGeneralInfo;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;

final class GetOperatorContributionGeneralInfoResponse
{
    public function __construct(
        public readonly OperatorContribution $contribution,
        public readonly string $propertyName,
        public readonly string $companyName,
        public readonly int $year,
    ) {}
}
