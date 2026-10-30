<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class OperatorContributionFilter
{
    use Filter;

    public function __construct(
        public ?CompanyCarbonFootprintId $companyCarbonFootprintId = null,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        public ?ProviderContributionStatus $status = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
