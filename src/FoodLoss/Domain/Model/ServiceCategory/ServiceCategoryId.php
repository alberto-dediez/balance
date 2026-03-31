<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ServiceCategoryId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
