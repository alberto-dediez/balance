<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EmissionFactorGroupId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
