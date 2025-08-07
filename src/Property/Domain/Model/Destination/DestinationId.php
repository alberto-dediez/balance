<?php

namespace App\Property\Domain\Model\Destination;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class DestinationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}