<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class CompanyCarbonFootprintFilter
{
    use Filter;

    public function __construct(
        public ?CompanyId $companyId = null,
        public ?int $year = null,
        public ?OrganizationalCarbonFootprintStatus $status = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
