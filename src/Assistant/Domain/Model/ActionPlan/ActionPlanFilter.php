<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class ActionPlanFilter
{
    use Filter;

    /** @param list<ActionPlanStatus>|null $statuses */
    public function __construct(
        public ?ConversationId $conversationId = null,
        public ?UserId $userId = null,
        public ?array $statuses = null,
        public ?\DateTimeImmutable $proposedBefore = null,
        public ?\DateTimeImmutable $lastProgressBefore = null,
        int $limit = 20,
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}
