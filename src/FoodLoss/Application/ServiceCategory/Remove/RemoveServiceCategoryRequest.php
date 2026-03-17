<?php

namespace App\FoodLoss\Application\ServiceCategory\Remove;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\Command;

class RemoveServiceCategoryRequest implements Command
{
    public function __construct(public ServiceCategoryId $serviceCategoryId)
    {

    }
}