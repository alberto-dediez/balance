<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UseProfileRemoved extends DomainEvent
{
    protected static string $eventName = 'useProfileRemoved';

    public function __construct(
        private UseProfileId $useProfileId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function useProfileId(): UseProfileId
    {
        return $this->useProfileId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->useProfileId();
    }

    public function payload(): array
    {
        return [
            'useProfileId' => (string) $this->useProfileId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new UseProfileRemoved(
            useProfileId: new UseProfileId($payload['useProfileId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
