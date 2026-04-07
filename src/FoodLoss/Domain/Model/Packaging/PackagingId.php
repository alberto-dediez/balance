<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PackagingId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
