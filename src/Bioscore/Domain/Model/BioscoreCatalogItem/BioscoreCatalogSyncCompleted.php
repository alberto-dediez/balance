<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogSyncCompleted extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogSyncCompleted';

    /**
     * @param list<array{itemId: string, catalog: string, bioscoreId: int, name: string}> $newPending
     * @param list<array{itemId: string, catalog: string, bioscoreId: int, name: string}> $invalidated
     * @param list<string>                                                                $failedCatalogs
     */
    public function __construct(
        private array $newPending,
        private array $invalidated,
        private array $failedCatalogs,
        private int $registered,
        private int $updated,
        private int $autoMapped,
        private int $withdrawn,
        private int $restored,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    /** @return list<array{itemId: string, catalog: string, bioscoreId: int, name: string}> */
    public function newPending(): array
    {
        return $this->newPending;
    }

    /** @return list<array{itemId: string, catalog: string, bioscoreId: int, name: string}> */
    public function invalidated(): array
    {
        return $this->invalidated;
    }

    /** @return list<string> */
    public function failedCatalogs(): array
    {
        return $this->failedCatalogs;
    }

    public function registered(): int
    {
        return $this->registered;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function autoMapped(): int
    {
        return $this->autoMapped;
    }

    public function withdrawn(): int
    {
        return $this->withdrawn;
    }

    public function restored(): int
    {
        return $this->restored;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    public function payload(): array
    {
        return [
            'newPending' => $this->newPending,
            'invalidated' => $this->invalidated,
            'failedCatalogs' => $this->failedCatalogs,
            'registered' => $this->registered,
            'updated' => $this->updated,
            'autoMapped' => $this->autoMapped,
            'withdrawn' => $this->withdrawn,
            'restored' => $this->restored,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BioscoreCatalogSyncCompleted(
            newPending: $payload['newPending'],
            invalidated: $payload['invalidated'],
            failedCatalogs: $payload['failedCatalogs'],
            registered: (int) $payload['registered'],
            updated: (int) $payload['updated'],
            autoMapped: (int) $payload['autoMapped'],
            withdrawn: (int) $payload['withdrawn'],
            restored: (int) $payload['restored'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
