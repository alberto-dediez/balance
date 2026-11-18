<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

final readonly class AiUsage
{
    public function __construct(
        public AiProvider $provider,
        public ?string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $cacheReadTokens = 0,
        public int $cacheCreationTokens = 0,
        public float $costUsd = 0.0,
        public int $durationMs = 0,
    ) {}

    public static function none(): self
    {
        return new self(AiProvider::Anthropic, null);
    }

    public function add(self $other): self
    {
        return new self(
            $this->provider,
            $other->model ?? $this->model,
            $this->inputTokens + $other->inputTokens,
            $this->outputTokens + $other->outputTokens,
            $this->cacheReadTokens + $other->cacheReadTokens,
            $this->cacheCreationTokens + $other->cacheCreationTokens,
            $this->costUsd + $other->costUsd,
            $this->durationMs + $other->durationMs,
        );
    }
}
