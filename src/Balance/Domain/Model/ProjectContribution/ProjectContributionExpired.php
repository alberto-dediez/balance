<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectContributionExpired extends DomainEvent
{
    protected static string $eventName = 'projectContributionExpired';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionExpired(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
