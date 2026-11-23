<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\ReceivedIntegrationEvent;

class ReceivedIntegrationEventMother
{
    public static function platformPinged(): ReceivedIntegrationEvent
    {
        return IntegrationEnvelopeMother::platformPinged()->toReceivedEvent();
    }

    /** @param array<string, mixed> $payload */
    public static function withPayload(array $payload): ReceivedIntegrationEvent
    {
        return IntegrationEnvelopeMother::from(payload: $payload)->toReceivedEvent();
    }

    public static function from(
        ?string $id = null,
        string $type = 'platform.pinged',
        int $version = 1,
        string $source = 'waste',
        int $sequence = 1,
        ?string $aggregateId = null,
        ?string $correlationId = null,
        int $hops = 0,
    ): ReceivedIntegrationEvent {
        return IntegrationEnvelopeMother::from(
            id: $id,
            type: $type,
            version: $version,
            source: $source,
            sequence: $sequence,
            aggregateId: $aggregateId,
            correlationId: $correlationId,
            hops: $hops,
        )->toReceivedEvent();
    }
}
