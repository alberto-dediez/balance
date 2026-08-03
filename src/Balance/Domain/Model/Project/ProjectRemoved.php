<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectRemoved extends DomainEvent
{
    protected static string $eventName = 'projectRemoved';

    public function __construct(
        private ProjectId $projectId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectId();
    }

    public function payload(): array
    {
        return [
            'projectId' => (string) $this->projectId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectRemoved(
            projectId: new ProjectId($payload['projectId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
