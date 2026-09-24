<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TwoFactorEnrolmentCancelled extends DomainEvent
{
    protected static string $eventName = 'twoFactorEnrolmentCancelled';

    public function __construct(
        private readonly UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TwoFactorEnrolmentCancelled(
            userId: new UserId($payload['userId'] ?? ''),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
