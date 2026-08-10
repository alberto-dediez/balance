<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectContributionFailed extends DomainEvent
{
    protected static string $eventName = 'projectContributionFailed';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        private string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
            'reason' => $this->reason,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionFailed(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            reason: (string) $payload['reason'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
