<?php

namespace App\Property\Application\Category\Update;

use App\Property\Domain\Model\Category\CategoryId;

class UpdateCategoryResponse
{
    public function __construct(public CategoryId $categoryId, public string $name)
    {
        
    }
}