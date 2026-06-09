<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertCheckQueue;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AlertCheckQueueId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
