<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Email;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EmailTrackingId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
