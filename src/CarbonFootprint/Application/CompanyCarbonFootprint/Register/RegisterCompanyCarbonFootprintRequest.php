<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterCompanyCarbonFootprintRequest implements Command
{
    public function __construct(
        public ?CompanyId $companyId = null,
        public int $year = 0,
        public ?AccountId $accountId = null,
        public ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ) {}
}
