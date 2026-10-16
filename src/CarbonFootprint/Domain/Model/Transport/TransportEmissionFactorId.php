<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class TransportEmissionFactorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
