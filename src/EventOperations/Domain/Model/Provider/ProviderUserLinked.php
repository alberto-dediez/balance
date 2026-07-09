<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProviderUserLinked extends DomainEvent
{
    protected static string $eventName = 'providerUserLinked';

    public function __construct(
        private ProviderId $providerId,
        private UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerId();
    }

    public function payload(): array
    {
        return [
            'providerId' => (string) $this->providerId,
            'userId' => (string) $this->userId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProviderUserLinked(
            providerId: new ProviderId($payload['providerId']),
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
