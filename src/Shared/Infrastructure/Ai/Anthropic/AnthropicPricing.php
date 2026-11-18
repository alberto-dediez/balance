<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai\Anthropic;

final class AnthropicPricing
{
    private const float TOKENS_PER_UNIT = 1_000_000.0;

    private const array PRICES = [
        'claude-opus-5-5' => [4.0, 20.0, 0.40, 5.0],
        'claude-sonnet-5-5' => [2.0, 10.0, 0.20, 2.50],
        'claude-haiku-5-5' => [0.10, 0.50, 0.01, 0.125],
        'claude-opus-5' => [5.0, 25.0, 0.50, 6.25],
        'claude-sonnet-5' => [2.0, 10.0, 0.20, 2.50],
    ];

    public function costOf(string $model, int $input, int $output, int $cacheRead, int $cacheCreation): float
    {
        $prices = self::PRICES[$model] ?? null;

        if (null === $prices) {
            return 0.0;
        }

        [$inputPrice, $outputPrice, $cacheReadPrice, $cacheCreationPrice] = $prices;

        return round(
            ($input * $inputPrice + $output * $outputPrice + $cacheRead * $cacheReadPrice + $cacheCreation * $cacheCreationPrice) / self::TOKENS_PER_UNIT,
            6,
        );
    }
}
