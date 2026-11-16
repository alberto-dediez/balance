<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Application\Ai\AiProvider;
use App\Shared\Application\Ai\AiUsage;

final readonly class AnthropicUsageReader
{
    public function __construct(private AnthropicPricing $pricing) {}

    /** @param array<mixed> $data */
    public function read(array $data, string $model, int $durationMs): AiUsage
    {
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $responseModel = is_string($data['model'] ?? null) && '' !== $data['model'] ? $data['model'] : $model;
        $input = $this->int($usage['input_tokens'] ?? 0);
        $output = $this->int($usage['output_tokens'] ?? 0);
        $cacheRead = $this->int($usage['cache_read_input_tokens'] ?? 0);
        $cacheCreation = $this->int($usage['cache_creation_input_tokens'] ?? 0);

        return new AiUsage(
            AiProvider::Anthropic,
            $responseModel,
            $input,
            $output,
            $cacheRead,
            $cacheCreation,
            $this->pricing->costOf($model, $input, $output, $cacheRead, $cacheCreation),
            $durationMs,
        );
    }

    private function int(mixed $value): int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }
}
