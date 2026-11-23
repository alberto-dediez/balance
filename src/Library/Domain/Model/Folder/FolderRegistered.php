<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Library\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class FolderRegistered extends DomainEvent
{
    protected static string $eventName = 'folderRegistered';

    public function __construct(
        private readonly FolderId $folderId,
        private readonly string $name,
        private readonly ?AccountId $accountId = null,
        private readonly ?FolderId $parentId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function folderId(): FolderId
    {
        return $this->folderId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function parentId(): ?FolderId
    {
        return $this->parentId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->folderId;
    }

    public function payload(): array
    {
        return [
            'folderId' => (string) $this->folderId,
            'name' => $this->name,
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'parentId' => $this->parentId instanceof FolderId ? (string) $this->parentId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new FolderRegistered(
            folderId: new FolderId($payload['folderId']),
            name: $payload['name'],
            accountId: null === $payload['accountId'] ? null : new AccountId($payload['accountId']),
            parentId: isset($payload['parentId']) ? new FolderId($payload['parentId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
