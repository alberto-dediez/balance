<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionSummary;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\Property\Domain\Model\Property\Property;

final class GetOperatorContributionDetailResponse
{
    /**
     * @param list<StageSection> $scope1Sections
     * @param list<StageSection> $scope2Sections
     */
    public function __construct(
        public readonly OperatorContribution $contribution,
        public readonly ?Property $property,
        public readonly array $scope1Sections,
        public readonly array $scope2Sections,
        public readonly OperatorContributionSummary $summary,
    ) {}
}
