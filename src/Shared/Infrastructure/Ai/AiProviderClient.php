<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiClient;
use App\Shared\Application\Ai\AiProvider;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shared.ai.provider')]
interface AiProviderClient extends AiClient
{
    public function provider(): AiProvider;
}
