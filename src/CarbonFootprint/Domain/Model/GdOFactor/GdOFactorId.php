<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class GdOFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
