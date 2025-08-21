<?php

namespace App\Property\Application\Category\Register;

use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCategoryResponse implements CommandResponse
{
    public function __construct(public CategoryId $categoryId, public string $name)
    {
        
    }
}