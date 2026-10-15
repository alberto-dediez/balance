<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

final class ExchangeRateSourceUnavailableException extends \RuntimeException
{
    public static function fromUrl(string $url, ?\Throwable $previous = null): self
    {
        return new self(sprintf('The exchange rates source "%s" is unavailable.', $url), 0, $previous);
    }

    public static function malformed(string $reason): self
    {
        return new self(sprintf('The exchange rates source returned a malformed document: %s.', $reason));
    }
}
