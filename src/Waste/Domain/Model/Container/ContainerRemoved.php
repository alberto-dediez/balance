<?php

namespace App\Waste\Domain\Model\Container;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Shared\Domain\Event\DomainEventId;

final class ContainerRemoved extends DomainEvent
{
    protected string $eventName = 'containerRemoved';

    public function __construct(
        private ContainerId $containerId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function containerId(): ContainerId
    {
        return $this->containerId;
    }

    public function payload(): ?array
    {
        return [
            'containerId' => (string) $this->containerId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ContainerRemoved(
            containerId: new ContainerId($payload['containerId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}