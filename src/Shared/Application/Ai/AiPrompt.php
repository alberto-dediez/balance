<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

interface AiPrompt
{
    public function name(): string;

    public function system(): string;

    public function text(): string;

    /** @return list<AiAttachment> */
    public function attachments(): array;

    /** @return array<mixed> */
    public function responseSchema(): array;

    public function modelTier(): AiModelTier;

    public function effort(): AiEffort;
}
