<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProviderRemoved extends DomainEvent
{
    protected static string $eventName = 'providerRemoved';

    public function __construct(
        private ProviderId $providerId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerId();
    }

    public function payload(): array
    {
        return [
            'providerId' => (string) $this->providerId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProviderRemoved(
            providerId: new ProviderId($payload['providerId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
