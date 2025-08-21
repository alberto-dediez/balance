<?php

namespace App\Property\Application\Category\Update;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;

class UpdateCategoryRequest implements Command
{
    public function __construct(public CategoryId $categoryId, public string $name, public ?ExternalId $externalId = null)
    {

    }
}