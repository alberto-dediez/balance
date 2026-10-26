<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

final class AiResponseException extends \RuntimeException
{
    public static function incomplete(string $model, string $finishReason, string $usage): self
    {
        return new self(sprintf('The AI response from %s did not finish (%s) after %s', $model, $finishReason, $usage));
    }

    public static function httpError(string $model, int $status, string $message): self
    {
        return new self(sprintf('The AI request to %s failed with HTTP %d: %s', $model, $status, $message));
    }

    public static function invalidJson(string $model, string $error): self
    {
        return new self(sprintf('The AI response from %s is not valid JSON: %s', $model, $error));
    }
}
