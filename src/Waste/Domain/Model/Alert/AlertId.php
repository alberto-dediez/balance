<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AlertId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
