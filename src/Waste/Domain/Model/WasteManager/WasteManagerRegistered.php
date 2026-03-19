<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class WasteManagerRegistered extends DomainEvent
{
    protected static string $eventName = 'wasteManagerRegistered';

    public function __construct(
        private readonly WasteManagerId $wasteManagerId,
        private readonly string $name,
        private readonly bool $isPublic,
        private readonly AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteManagerId;
    }

    public function payload(): array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
            'accountId' => (string) $this->accountId,
            'name' => $this->name,
            'isPublic' => $this->isPublic,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new WasteManagerRegistered(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']),
            name: $payload['name'],
            isPublic: $payload['isPublic'],
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
