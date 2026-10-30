<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetData;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;

final class GetOperatorContributionDataResponse
{
    /**
     * @param list<StageSection> $scope1Sections
     * @param list<StageSection> $scope2Sections
     */
    public function __construct(
        public readonly OperatorContribution $contribution,
        public readonly array $scope1Sections,
        public readonly array $scope2Sections,
    ) {}
}
