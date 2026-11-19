<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

final class IntegrationUserNotAvailable extends \RuntimeException
{
    public static function because(string $reason): self
    {
        return new self(sprintf('Integration user not available: %s', $reason));
    }
}
