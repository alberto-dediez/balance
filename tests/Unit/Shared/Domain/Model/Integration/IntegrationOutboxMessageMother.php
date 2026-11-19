<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use App\Shared\Domain\Uuid\UuidGenerator;

class IntegrationOutboxMessageMother
{
    public static function pending(string $type = 'platform.pinged', ?\DateTimeImmutable $occurredOn = null): IntegrationOutboxMessage
    {
        return self::from(type: $type, occurredOn: $occurredOn);
    }

    public static function sentAt(\DateTimeImmutable $sentAt): IntegrationOutboxMessage
    {
        return self::from(occurredOn: $sentAt, sentAt: $sentAt);
    }

    public static function from(
        ?int $sequence = null,
        ?string $messageId = null,
        string $type = 'platform.pinged',
        int $version = 1,
        ?string $aggregateId = null,
        ?string $correlationId = null,
        ?string $causationId = null,
        int $hops = 0,
        ?\DateTimeImmutable $occurredOn = null,
        string $payload = '{"message":"hola"}',
        ?\DateTimeImmutable $sentAt = null,
    ): IntegrationOutboxMessage {
        $messageId ??= UuidGenerator::v7();

        return new IntegrationOutboxMessage(
            sequence: $sequence,
            messageId: $messageId,
            type: $type,
            version: $version,
            aggregateId: $aggregateId,
            accountRef: null,
            correlationId: $correlationId ?? $messageId,
            causationId: $causationId,
            hops: $hops,
            occurredOn: $occurredOn ?? new \DateTimeImmutable('NOW', new \DateTimeZone('UTC')),
            payload: $payload,
            sentAt: $sentAt,
        );
    }
}
