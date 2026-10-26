<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

interface AiPrompt
{
    public function text(): string;

    /** @return array<mixed> */
    public function responseSchema(): array;

    public function modelTier(): AiModelTier;

    public function thinkingLevel(): AiThinkingLevel;
}
