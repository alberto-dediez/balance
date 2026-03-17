<?php

namespace App\FoodLoss\Application\ServiceCategory\Remove;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveServiceCategoryResponse implements CommandResponse
{
    public function __construct(public ServiceCategoryId $serviceCategoryId)
    {
        
    }
}