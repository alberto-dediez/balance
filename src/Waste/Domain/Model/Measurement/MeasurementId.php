<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MeasurementId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
