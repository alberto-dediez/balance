<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

use App\Shared\Application\Ai\AiUsage;

final readonly class AiAgentStep
{
    public function __construct(
        public AiAgentMessage $message,
        public AiUsage $usage,
        public AiStopReason $stopReason,
    ) {}

    public function text(): string
    {
        $texts = [];

        foreach ($this->message->blocks as $block) {
            if ($block->isText()) {
                $texts[] = $block->text;
            }
        }

        return trim(implode("\n\n", $texts));
    }

    /** @return list<AiToolCall> */
    public function toolCalls(): array
    {
        $calls = [];

        foreach ($this->message->blocks as $block) {
            if ($block->isToolUse()) {
                $calls[] = new AiToolCall($block->id, $block->name, $block->input);
            }
        }

        return $calls;
    }
}
