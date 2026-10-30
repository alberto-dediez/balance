<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;

final class OperatorContributionSummary
{
    public function __construct(
        public readonly OperatorContribution $contribution,
        public readonly Emissions $scope1,
        public readonly Emissions $scope2,
        public readonly Emissions $total,
    ) {}
}
