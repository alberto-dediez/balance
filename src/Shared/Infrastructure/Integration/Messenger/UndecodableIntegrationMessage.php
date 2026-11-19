<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration\Messenger;

final readonly class UndecodableIntegrationMessage
{
    public function __construct(
        public string $body,
        public string $reason,
    ) {}
}
