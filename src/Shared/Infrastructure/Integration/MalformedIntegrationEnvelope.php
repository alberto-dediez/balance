<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

final class MalformedIntegrationEnvelope extends \InvalidArgumentException
{
    public static function because(string $reason): self
    {
        return new self(sprintf('Malformed integration envelope: %s', $reason));
    }
}
