<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class ConversationFilter
{
    use Filter;

    public function __construct(
        public ?UserId $userId = null,
        public ?\DateTimeImmutable $lastActivityBefore = null,
        public ?\DateTimeImmutable $pendingMessageBefore = null,
        int $limit = 20,
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}
