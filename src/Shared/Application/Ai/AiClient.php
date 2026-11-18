<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

interface AiClient
{
    public function send(AiPrompt $prompt): AiResponse;
}
