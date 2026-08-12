<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PublicLandingRegistered extends DomainEvent
{
    protected static string $eventName = 'publicLandingRegistered';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private PublicLandingOwnerType $ownerType,
        private string $ownerId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function publicLandingId(): PublicLandingId
    {
        return $this->publicLandingId;
    }

    public function ownerType(): PublicLandingOwnerType
    {
        return $this->ownerType;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->publicLandingId();
    }

    public function payload(): array
    {
        return [
            'publicLandingId' => (string) $this->publicLandingId,
            'ownerType' => $this->ownerType->value,
            'ownerId' => $this->ownerId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        $ownerType = PublicLandingOwnerType::tryFrom($payload['ownerType']);
        assert(null !== $ownerType);

        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            ownerType: $ownerType,
            ownerId: $payload['ownerId'],
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
