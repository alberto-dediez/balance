<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TripCalculationLinkRegistered extends DomainEvent
{
    protected static string $eventName = 'tripCalculationLinkRegistered';

    public function __construct(
        private TripCalculationLinkId $tripCalculationLinkId,
        private ?PropertyId $propertyId,
        private AccountId $accountId,
        #[\SensitiveParameter]
        private string $token,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function tripCalculationLinkId(): TripCalculationLinkId
    {
        return $this->tripCalculationLinkId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->tripCalculationLinkId();
    }

    public function payload(): array
    {
        return [
            'tripCalculationLinkId' => (string) $this->tripCalculationLinkId,
            'propertyId' => null !== $this->propertyId ? (string) $this->propertyId : null,
            'accountId' => (string) $this->accountId,
            'token' => $this->token,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            tripCalculationLinkId: new TripCalculationLinkId($payload['tripCalculationLinkId']),
            propertyId: null !== ($payload['propertyId'] ?? null) ? new PropertyId($payload['propertyId']) : null,
            accountId: new AccountId($payload['accountId']),
            token: $payload['token'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
