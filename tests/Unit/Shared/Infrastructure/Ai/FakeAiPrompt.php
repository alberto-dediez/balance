<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiAttachment;
use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;
use App\Shared\Application\Ai\AiPrompt;

final readonly class FakeAiPrompt implements AiPrompt
{
    /** @param list<AiAttachment> $attachments */
    public function __construct(
        private array $attachments = [],
        private AiModelTier $tier = AiModelTier::MEDIUM,
        private AiEffort $effort = AiEffort::MEDIUM,
        private string $name = 'fake-prompt',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function system(): string
    {
        return 'System';
    }

    public function text(): string
    {
        return 'Prompt';
    }

    public function attachments(): array
    {
        return $this->attachments;
    }

    public function responseSchema(): array
    {
        return ['type' => 'OBJECT', 'properties' => ['content' => ['type' => 'STRING']], 'required' => ['content']];
    }

    public function modelTier(): AiModelTier
    {
        return $this->tier;
    }

    public function effort(): AiEffort
    {
        return $this->effort;
    }
}
