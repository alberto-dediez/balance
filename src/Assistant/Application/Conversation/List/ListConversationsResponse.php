<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\List;

use App\Assistant\Domain\Model\Conversation\Conversation;

class ListConversationsResponse
{
    /** @param list<Conversation> $conversations */
    public function __construct(public array $conversations = [], public int $totalResults = 0) {}
}
