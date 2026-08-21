<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class TripCalculationLink implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private TripCalculationLinkId $id,
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        #[\SensitiveParameter]
        private string $token = '',
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private TripCalculationScope $scope = TripCalculationScope::Both,
    ) {
        $this->events = $events;
    }

    public function id(): TripCalculationLinkId
    {
        return $this->id;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function scope(): TripCalculationScope
    {
        return $this->scope;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        PropertyId $propertyId,
        AccountId $accountId,
        #[\SensitiveParameter]
        string $token,
    ): void {
        $this->recordApplyAndPublish(
            new TripCalculationLinkRegistered(
                tripCalculationLinkId: $this->id(),
                propertyId: $propertyId,
                accountId: $accountId,
                token: $token,
            ),
        );
    }

    public function updateScope(TripCalculationScope $scope): void
    {
        $this->recordApplyAndPublish(
            new TripCalculationLinkScopeUpdated(
                tripCalculationLinkId: $this->id(),
                scope: $scope,
            ),
        );
    }

    public function applyTripCalculationLinkRegistered(TripCalculationLinkRegistered $event): void
    {
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->token = $event->token();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTripCalculationLinkScopeUpdated(TripCalculationLinkScopeUpdated $event): void
    {
        $this->scope = $event->scope();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
