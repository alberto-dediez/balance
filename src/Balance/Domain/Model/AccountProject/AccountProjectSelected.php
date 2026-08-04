<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountProjectSelected extends DomainEvent
{
    protected static string $eventName = 'accountProjectSelected';

    public function __construct(
        private AccountProjectId $accountProjectId,
        private AccountId $accountId,
        private ProjectId $projectId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountProjectId(): AccountProjectId
    {
        return $this->accountProjectId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ProjectId
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
            'accountId' => (string) $this->accountId,
            'projectId' => (string) $this->projectId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountProjectSelected(
            accountProjectId: new AccountProjectId($payload['accountProjectId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
