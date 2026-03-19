<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ContainerId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
