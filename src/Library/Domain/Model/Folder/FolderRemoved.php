<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class FolderRemoved extends DomainEvent
{
    protected static string $eventName = 'folderRemoved';

    public function __construct(
        private readonly FolderId $folderId,
        private readonly string $name,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->folderId;
    }

    public function payload(): array
    {
        return [
            'folderId' => (string) $this->folderId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new FolderRemoved(
            folderId: new FolderId($payload['folderId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
