<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CombustibleRemoved extends DomainEvent
{
    protected static string $eventName = 'combustibleRemoved';

    public function __construct(
        private CombustibleId $combustibleId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function combustibleId(): CombustibleId
    {
        return $this->combustibleId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->combustibleId();
    }

    public function payload(): array
    {
        return [
            'combustibleId' => (string) $this->combustibleId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CombustibleRemoved(
            combustibleId: new CombustibleId($payload['combustibleId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
