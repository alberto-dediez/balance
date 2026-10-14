<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Update;

use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCategoryResponse implements CommandResponse
{
    public function __construct(public CategoryId $categoryId, public string $name) {}
}
