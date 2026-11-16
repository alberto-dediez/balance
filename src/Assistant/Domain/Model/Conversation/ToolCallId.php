<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ToolCallId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
