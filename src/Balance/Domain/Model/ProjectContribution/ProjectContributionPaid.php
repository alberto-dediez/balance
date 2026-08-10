<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectContributionPaid extends DomainEvent
{
    protected static string $eventName = 'projectContributionPaid';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        private ?AccountId $accountId,
        private ?ProjectId $projectId,
        private float $tonnes,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function tonnes(): float
    {
        return $this->tonnes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
            'accountId' => null !== $this->accountId ? (string) $this->accountId : null,
            'projectId' => null !== $this->projectId ? (string) $this->projectId : null,
            'tonnes' => $this->tonnes,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionPaid(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            projectId: null !== $payload['projectId'] ? new ProjectId($payload['projectId']) : null,
            tonnes: (float) $payload['tonnes'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
