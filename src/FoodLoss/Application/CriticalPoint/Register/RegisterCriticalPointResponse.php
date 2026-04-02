<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCriticalPointResponse implements CommandResponse
{
    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name,
        public array $serviceCategoryIds = [],
    ) {}
}
