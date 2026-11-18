<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model;

final class AssistantUsage
{
    public function __construct(
        private readonly ?string $model = null,
        private readonly int $inputTokens = 0,
        private readonly int $outputTokens = 0,
        private readonly int $cacheReadTokens = 0,
        private readonly int $cacheCreationTokens = 0,
        private readonly string $costUsd = '0.000000',
        private readonly int $durationMs = 0,
    ) {}

    public static function none(): self
    {
        return new self();
    }

    public function add(self $other): self
    {
        return new self(
            $other->model ?? $this->model,
            $this->inputTokens + $other->inputTokens,
            $this->outputTokens + $other->outputTokens,
            $this->cacheReadTokens + $other->cacheReadTokens,
            $this->cacheCreationTokens + $other->cacheCreationTokens,
            self::formatCost((float) $this->costUsd + (float) $other->costUsd),
            $this->durationMs + $other->durationMs,
        );
    }

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        $model = $payload['model'] ?? null;

        return new self(
            is_string($model) ? $model : null,
            self::intOf($payload['inputTokens'] ?? 0),
            self::intOf($payload['outputTokens'] ?? 0),
            self::intOf($payload['cacheReadTokens'] ?? 0),
            self::intOf($payload['cacheCreationTokens'] ?? 0),
            self::formatCost(is_numeric($payload['costUsd'] ?? null) ? (float) $payload['costUsd'] : 0.0),
            self::intOf($payload['durationMs'] ?? 0),
        );
    }

    /** @return array{model: ?string, inputTokens: int, outputTokens: int, cacheReadTokens: int, cacheCreationTokens: int, costUsd: string, durationMs: int} */
    public function toPayload(): array
    {
        return [
            'model' => $this->model,
            'inputTokens' => $this->inputTokens,
            'outputTokens' => $this->outputTokens,
            'cacheReadTokens' => $this->cacheReadTokens,
            'cacheCreationTokens' => $this->cacheCreationTokens,
            'costUsd' => $this->costUsd(),
            'durationMs' => $this->durationMs,
        ];
    }

    public static function formatCost(float $cost): string
    {
        return sprintf('%.6F', $cost);
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function inputTokens(): int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): int
    {
        return $this->outputTokens;
    }

    public function cacheReadTokens(): int
    {
        return $this->cacheReadTokens;
    }

    public function cacheCreationTokens(): int
    {
        return $this->cacheCreationTokens;
    }

    public function costUsd(): string
    {
        return self::formatCost((float) $this->costUsd);
    }

    public function durationMs(): int
    {
        return $this->durationMs;
    }

    private static function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
