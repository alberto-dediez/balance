<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

interface AiAgentClient
{
    public function step(AiAgentRequest $request): AiAgentStep;
}
