<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Update;

use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Application\CommandBus\Command;

class UpdateCategoryRequest implements Command
{
    public function __construct(public CategoryId $categoryId, public string $name, public ?ExternalId $externalId = null) {}
}
