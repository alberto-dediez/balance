<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class IncidenceId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
