<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProtocolRegistered extends DomainEvent
{
    protected static string $eventName = 'protocolRegistered';

    public function __construct(
        private ProtocolId $protocolId,
        private string $name,
        private string $description,
        private string $path,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function protocolId(): ProtocolId
    {
        return $this->protocolId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->protocolId();
    }

    public function payload(): array
    {
        return [
            'protocolId' => (string) $this->protocolId,
            'name' => $this->name,
            'description' => $this->description,
            'path' => $this->path,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProtocolRegistered(
            protocolId: new ProtocolId($payload['protocolId']),
            name: $payload['name'],
            description: $payload['description'],
            path: $payload['path'],
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
