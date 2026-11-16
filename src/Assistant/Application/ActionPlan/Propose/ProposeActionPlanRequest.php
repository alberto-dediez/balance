<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Propose;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\Command;

class ProposeActionPlanRequest implements Command
{
    /**
     * @param list<array{tool: string, arguments: list<array{name: string, value: string, fromStep: ?int}>}> $steps
     */
    public function __construct(
        public ConversationId $conversationId,
        public string $summary,
        public array $steps,
        public string $timezone = 'UTC',
    ) {}
}
