<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

final class GeminiResponseException extends \RuntimeException
{
    public static function incomplete(string $model, string $finishReason, int $outputTokens, int $thoughtsTokens): self
    {
        return new self(sprintf('The Gemini response from %s did not finish (%s) after %d output tokens and %d thinking tokens', $model, $finishReason, $outputTokens, $thoughtsTokens));
    }

    public static function invalidJson(string $model, string $error): self
    {
        return new self(sprintf('The Gemini response from %s is not valid JSON: %s', $model, $error));
    }
}
