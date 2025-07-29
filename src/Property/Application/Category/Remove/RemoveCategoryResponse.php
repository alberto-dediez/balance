<?php

namespace App\Property\Application\Category\Remove;

use App\Property\Domain\Model\Category\CategoryId;

class RemoveCategoryResponse
{
    public function __construct(public CategoryId $categoryId)
    {
        
    }
}