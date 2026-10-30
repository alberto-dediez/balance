<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Show;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;

final class GetOperatorContributionDetailRequest
{
    public function __construct(
        public readonly OperatorContributionId $contributionId,
    ) {}
}
