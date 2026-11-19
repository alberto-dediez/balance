<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration\Messenger;

use App\Shared\Infrastructure\Integration\IntegrationEnvelope;

final readonly class OutgoingIntegrationMessage
{
    public function __construct(public IntegrationEnvelope $envelope) {}
}
