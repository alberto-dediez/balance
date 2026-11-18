<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\View;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\Conversation\Message;

final readonly class ThreadEntry
{
    public function __construct(
        public Message $message,
        public ?ActionPlan $plan = null,
        public bool $executable = false,
    ) {}
}
