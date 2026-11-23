<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationInboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationInboxStatus;
use App\Shared\Domain\Uuid\UuidGenerator;

class IntegrationInboxMessageMother
{
    public static function processed(?string $messageId = null, ?\DateTimeImmutable $receivedAt = null): IntegrationInboxMessage
    {
        return self::from(messageId: $messageId, receivedAt: $receivedAt);
    }

    public static function from(
        ?string $messageId = null,
        string $source = 'waste',
        string $type = 'platform.pinged',
        int $version = 1,
        ?string $aggregateId = null,
        int $sequence = 1,
        IntegrationInboxStatus $status = IntegrationInboxStatus::Processed,
        ?\DateTimeImmutable $receivedAt = null,
    ): IntegrationInboxMessage {
        $messageId ??= UuidGenerator::v7();

        return new IntegrationInboxMessage(
            messageId: $messageId,
            source: $source,
            type: $type,
            version: $version,
            aggregateId: $aggregateId,
            sequence: $sequence,
            correlationId: $messageId,
            causationId: null,
            hops: 0,
            status: $status,
            receivedAt: $receivedAt ?? new \DateTimeImmutable('NOW'),
        );
    }
}
