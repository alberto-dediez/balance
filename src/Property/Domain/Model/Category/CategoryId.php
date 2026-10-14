<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Category;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CategoryId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
