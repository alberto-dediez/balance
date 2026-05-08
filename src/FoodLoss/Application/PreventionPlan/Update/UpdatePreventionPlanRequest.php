<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Update;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdatePreventionPlanRequest implements Command
{
    /** @param PropertyId[] $properties */
    public function __construct(
        public PreventionPlanId $preventionPlanId,
        public string $name,
        public array $properties = [],
        public ?CompanyId $companyId = null,
        public ?EmployeeId $responsible = null,
        public bool $useAllCompanyProperties = false,
    ) {}
}
