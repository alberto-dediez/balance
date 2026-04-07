<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PackagingRegistered extends DomainEvent
{
    protected static string $eventName = 'packagingRegistered';

    public function __construct(
        private PackagingId $packagingId,
        private string $name,
        private PackagingType $packagingType,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function packagingId(): PackagingId
    {
        return $this->packagingId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function packagingType(): PackagingType
    {
        return $this->packagingType;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->packagingId();
    }

    public function payload(): array
    {
        return [
            'packagingId' => (string) $this->packagingId,
            'name' => $this->name,
            'packagingType' => $this->packagingType->value,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $packagingType = PackagingType::tryFrom($payload['packagingType']);
        assert(null !== $packagingType);

        return new PackagingRegistered(
            packagingId: new PackagingId($payload['packagingId']),
            name: $payload['name'],
            packagingType: $packagingType,
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
