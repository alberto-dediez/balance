<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;

final readonly class AiAgentRequest
{
    /**
     * @param list<AiToolDefinition> $tools
     * @param list<AiAgentMessage>   $messages
     */
    public function __construct(
        public string $name,
        public string $system,
        public array $tools,
        public array $messages,
        public AiModelTier $modelTier,
        public AiEffort $effort,
        public bool $parallelToolCalls = true,
    ) {}
}
