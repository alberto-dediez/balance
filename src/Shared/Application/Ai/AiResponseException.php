<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

final class AiResponseException extends \RuntimeException
{
    private ?AiUsage $usage = null;

    public static function incomplete(string $model, string $stopReason, AiUsage $usage): self
    {
        $exception = new self(sprintf('The AI response from %s did not finish (%s) after %d output tokens', $model, $stopReason, $usage->outputTokens));
        $exception->usage = $usage;

        return $exception;
    }

    public static function httpError(string $model, int $status, string $message): self
    {
        return new self(sprintf('The AI request to %s failed with HTTP %d: %s', $model, $status, $message));
    }

    public static function invalidJson(string $model, string $error, ?AiUsage $usage = null): self
    {
        $exception = new self(sprintf('The AI response from %s is not valid JSON: %s', $model, $error));
        $exception->usage = $usage;

        return $exception;
    }

    public static function providerNotConfigured(AiProvider|string $provider): self
    {
        return new self(sprintf('The AI provider "%s" is not configured', $provider instanceof AiProvider ? $provider->value : $provider));
    }

    public static function unsupportedAttachment(string $mimeType): self
    {
        return new self(sprintf('The AI provider does not support attachments of type "%s"', $mimeType));
    }

    public function usage(): ?AiUsage
    {
        return $this->usage;
    }
}
