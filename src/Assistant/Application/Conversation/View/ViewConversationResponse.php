<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\View;

use App\Assistant\Domain\Model\Conversation\Conversation;

class ViewConversationResponse
{
    /**
     * @param list<ThreadEntry>                    $entries
     * @param array<string, string>                $toolLabels
     * @param array<string, array<string, string>> $parameterLabels
     */
    public function __construct(
        public Conversation $conversation,
        public array $entries,
        public bool $busy,
        public string $spentToday,
        public string $dailyLimit,
        public array $toolLabels,
        public array $parameterLabels = [],
        public string $fingerprint = '',
    ) {}
}
