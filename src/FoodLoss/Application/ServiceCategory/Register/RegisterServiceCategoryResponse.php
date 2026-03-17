<?php

namespace App\FoodLoss\Application\ServiceCategory\Register;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\Priority;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterServiceCategoryResponse implements CommandResponse
{
    public function __construct(
        public ServiceCategoryId $serviceCategoryId, 
        public string $name
    ) {
        
    }
}