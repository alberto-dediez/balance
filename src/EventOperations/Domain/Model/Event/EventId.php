<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EventId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
