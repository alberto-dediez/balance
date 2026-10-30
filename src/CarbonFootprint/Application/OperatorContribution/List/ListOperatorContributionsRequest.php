<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\List;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;

final class ListOperatorContributionsRequest
{
    public function __construct(
        public readonly CompanyCarbonFootprintId $companyCarbonFootprintId,
    ) {}
}
