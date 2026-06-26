<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Combustible;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CombustibleEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
