<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

final readonly class AiAgentMessage
{
    /** @param list<AiAgentBlock> $blocks */
    public function __construct(
        public AiAgentRole $role,
        public array $blocks,
    ) {}

    public static function userText(string $text): self
    {
        return new self(AiAgentRole::User, [AiAgentBlock::text($text)]);
    }

    public static function assistantText(string $text): self
    {
        return new self(AiAgentRole::Assistant, [AiAgentBlock::text($text)]);
    }

    /** @param list<AiAgentBlock> $results */
    public static function toolResults(array $results): self
    {
        return new self(AiAgentRole::User, $results);
    }
}
