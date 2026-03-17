<?php

namespace App\FoodLoss\Application\ServiceCategory\Update;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\Command;

class UpdateServiceCategoryRequest implements Command
{
    public function __construct(
        public ServiceCategoryId $serviceCategoryId,
        public string $name
    ) {

    }
}