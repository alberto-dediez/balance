<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountProjectDeselected extends DomainEvent
{
    protected static string $eventName = 'accountProjectDeselected';

    public function __construct(
        private AccountProjectId $accountProjectId,
        private ?AccountId $accountId,
        private ?ProjectId $projectId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountProjectId(): AccountProjectId
    {
        return $this->accountProjectId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountProjectId();
    }

    public function payload(): array
    {
        return [
            'accountProjectId' => (string) $this->accountProjectId,
            'accountId' => null !== $this->accountId ? (string) $this->accountId : null,
            'projectId' => null !== $this->projectId ? (string) $this->projectId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountProjectDeselected(
            accountProjectId: new AccountProjectId($payload['accountProjectId']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            projectId: null !== $payload['projectId'] ? new ProjectId($payload['projectId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
