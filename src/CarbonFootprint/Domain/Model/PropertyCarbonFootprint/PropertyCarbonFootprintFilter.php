<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class PropertyCarbonFootprintFilter
{
    use Filter;

    public function __construct(
        public ?PropertyId $propertyId = null,
        public ?int $year = null,
        public ?OrganizationalCarbonFootprintStatus $status = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
