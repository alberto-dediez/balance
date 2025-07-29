<?php

namespace App\Property\Application\Category\Register;

use App\Property\Domain\Model\Category\CategoryId;

class RegisterCategoryResponse
{
    public function __construct(public CategoryId $categoryId, public string $name)
    {
        
    }
}