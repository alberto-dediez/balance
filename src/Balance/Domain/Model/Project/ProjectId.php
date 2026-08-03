<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProjectId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
