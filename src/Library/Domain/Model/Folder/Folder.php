<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Folder implements \Stringable
{
    use AggregateRoot;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected FolderId $id,
        protected ?AccountId $accountId = null,
        protected string $name = '',
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public function id(): FolderId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(string $name, ?AccountId $accountId = null): void
    {
        $this->recordApplyAndPublish(
            new FolderRegistered(
                folderId: $this->id(),
                name: $name,
                accountId: $accountId,
            ),
        );
    }

    public function rename(string $name): void
    {
        $this->recordApplyAndPublish(
            new FolderRenamed(
                folderId: $this->id(),
                name: $name,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new FolderRemoved(
                folderId: $this->id(),
                name: $this->name(),
            ),
        );
    }

    public function applyFolderRegistered(FolderRegistered $event): void
    {
        $this->name = $event->name();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyFolderRenamed(FolderRenamed $event): void
    {
        $this->name = $event->name();
    }

    public function applyFolderRemoved(FolderRemoved $event): void {}

    public function __toString(): string
    {
        return $this->name();
    }
}
