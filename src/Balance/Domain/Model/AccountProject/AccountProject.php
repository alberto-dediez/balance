<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class AccountProject
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private AccountProjectId $id,
        private ?AccountId $accountId = null,
        private ?ProjectId $projectId = null,
        array $events = [],
        private \DateTimeImmutable $selectedAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public function id(): AccountProjectId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function selectedAt(): \DateTimeImmutable
    {
        return $this->selectedAt;
    }

    public function select(AccountId $accountId, ProjectId $projectId): void
    {
        $this->recordApplyAndPublish(
            new AccountProjectSelected(
                accountProjectId: $this->id(),
                accountId: $accountId,
                projectId: $projectId,
            ),
        );
    }

    public function deselect(): void
    {
        $this->recordApplyAndPublish(
            new AccountProjectDeselected(
                accountProjectId: $this->id(),
                accountId: $this->accountId(),
                projectId: $this->projectId(),
            ),
        );
    }

    public function applyAccountProjectSelected(AccountProjectSelected $event): void
    {
        $this->accountId = $event->accountId();
        $this->projectId = $event->projectId();
        $this->selectedAt = $event->occurredOn();
    }

    public function applyAccountProjectDeselected(AccountProjectDeselected $event): void {}
}
