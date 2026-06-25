<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MaterialEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
