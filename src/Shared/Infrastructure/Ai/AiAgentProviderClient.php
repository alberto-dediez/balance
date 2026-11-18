<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\Agent\AiAgentClient;
use App\Shared\Application\Ai\AiProvider;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.ai.agent_provider')]
interface AiAgentProviderClient extends AiAgentClient
{
    public function provider(): AiProvider;
}
