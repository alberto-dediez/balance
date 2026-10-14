<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class JobProgressId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
