<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class LedgerEntryId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
