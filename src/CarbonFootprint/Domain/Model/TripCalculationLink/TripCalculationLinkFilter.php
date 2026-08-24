<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class TripCalculationLinkFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
