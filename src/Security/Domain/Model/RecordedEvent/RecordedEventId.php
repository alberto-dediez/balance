<?php

namespace App\Security\Domain\Model\RecordedEvent;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class RecordedEventId implements AggregateRootId
{
    use AggregateRootIdTrait;
}