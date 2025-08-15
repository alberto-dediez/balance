<?php

namespace App\Waste\Domain\Model\Container;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\Model\Container\ContainerId;

final class ContainerQrPrinted extends DomainEvent
{
    protected static string $eventName = 'containerQrPrinted';

    public function __construct(
        private ContainerId $containerId, 
        private string $path,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function containerId(): ContainerId
    {
        return $this->containerId;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->containerId();
    }

    public function payload(): ?array
    {
        return [
            'containerId' => (string) $this->containerId,
            'path' => (string) $this->path
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ContainerQrPrinted(
            containerId: new ContainerId($payload['containerId']),
            path: $payload['path'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}