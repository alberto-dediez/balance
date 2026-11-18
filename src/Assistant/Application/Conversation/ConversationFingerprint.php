<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\Conversation\Conversation;

final class ConversationFingerprint
{
    /** @param iterable<ActionPlan> $plans */
    public static function of(Conversation $conversation, iterable $plans): string
    {
        $messages = $conversation->messages();
        $last = [] === $messages ? null : $messages[array_key_last($messages)];
        $parts = [(string) count($messages), $last?->status()->value ?? ''];

        foreach ($plans as $plan) {
            $parts[] = sprintf('%s:%s:%d', $plan->id(), $plan->status()->value, $plan->doneSteps());
        }

        return md5(implode('|', $parts));
    }
}
