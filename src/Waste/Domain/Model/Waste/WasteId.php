<?php

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class WasteId implements AggregateRootId
{
    use AggregateRootIdTrait;
}