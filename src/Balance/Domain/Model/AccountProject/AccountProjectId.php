<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AccountProjectId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
