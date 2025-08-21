<?php

namespace App\Property\Application\Category\Remove;

use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveCategoryResponse implements CommandResponse
{
    public function __construct(public CategoryId $categoryId)
    {
        
    }
}