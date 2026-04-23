<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Register;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterPreventionPlanRequest implements Command
{
    /** @param PropertyId[] $properties */
    public function __construct(
        public string $name = '',
        public array $properties = [],
        public ?CompanyId $companyId = null,
        public ?AccountId $accountId = null,
    ) {}
}
