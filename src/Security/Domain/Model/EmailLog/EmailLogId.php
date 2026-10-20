<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EmailLogId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
