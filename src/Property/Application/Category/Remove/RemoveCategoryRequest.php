<?php

namespace App\Property\Application\Category\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Category\CategoryId;

class RemoveCategoryRequest implements Command
{
    public function __construct(public CategoryId $categoryId)
    {

    }
}