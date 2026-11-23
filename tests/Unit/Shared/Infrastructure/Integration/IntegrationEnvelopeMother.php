<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Domain\Uuid\UuidGenerator;
use App\Shared\Infrastructure\Integration\IntegrationEnvelope;

class IntegrationEnvelopeMother
{
    public static function platformPinged(): IntegrationEnvelope
    {
        return self::from(
            id: '0193a1c2-7f00-7c3e-9b1d-2f6c1a9e4b10',
            source: 'waste',
            sequence: 1234,
            occurredOn: new \DateTimeImmutable('2026-11-19T10:15:00+00:00'),
            payload: ['message' => 'hola'],
        );
    }

    /** @return array<string, mixed> */
    public static function platformPingedArray(): array
    {
        return self::platformPinged()->toArray();
    }

    /** @param array<string, mixed> $payload */
    public static function from(
        ?string $id = null,
        string $type = 'platform.pinged',
        int $version = 1,
        string $source = 'waste',
        int $sequence = 1,
        ?\DateTimeImmutable $occurredOn = null,
        ?string $aggregateId = null,
        ?string $correlationId = null,
        ?string $causationId = null,
        int $hops = 0,
        array $payload = ['message' => 'hola'],
    ): IntegrationEnvelope {
        $id ??= UuidGenerator::v7();

        return new IntegrationEnvelope(
            id: $id,
            type: $type,
            version: $version,
            source: $source,
            sequence: $sequence,
            occurredOn: $occurredOn ?? new \DateTimeImmutable('NOW', new \DateTimeZone('UTC')),
            aggregateId: $aggregateId,
            accountRef: null,
            correlationId: $correlationId ?? $id,
            causationId: $causationId,
            hops: $hops,
            payload: $payload,
        );
    }
}
