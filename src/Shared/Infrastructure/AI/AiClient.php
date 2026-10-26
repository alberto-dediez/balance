<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

interface AiClient
{
    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array;
}
