<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\Register;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterServiceCategoryResponse implements CommandResponse
{
    public function __construct(
        public ServiceCategoryId $serviceCategoryId,
        public string $name,
    ) {}
}
