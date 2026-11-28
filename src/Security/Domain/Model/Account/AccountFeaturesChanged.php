<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountFeaturesChanged extends DomainEvent
{
    protected static string $eventName = 'accountFeaturesChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly bool $accountTripCalculator,
        private readonly bool $propertyTripCalculators,
        private readonly bool $events,
        private readonly bool $compensation,
        private readonly bool $organizationalCarbonFootprint,
        private readonly bool $contests = false,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function accountTripCalculator(): bool
    {
        return $this->accountTripCalculator;
    }

    public function propertyTripCalculators(): bool
    {
        return $this->propertyTripCalculators;
    }

    public function events(): bool
    {
        return $this->events;
    }

    public function compensation(): bool
    {
        return $this->compensation;
    }

    public function organizationalCarbonFootprint(): bool
    {
        return $this->organizationalCarbonFootprint;
    }

    public function contests(): bool
    {
        return $this->contests;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
    {
        return [
            'accountId' => (string) $this->accountId,
            'accountTripCalculator' => $this->accountTripCalculator,
            'propertyTripCalculators' => $this->propertyTripCalculators,
            'events' => $this->events,
            'compensation' => $this->compensation,
            'organizationalCarbonFootprint' => $this->organizationalCarbonFootprint,
            'contests' => $this->contests,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        assert(is_bool($payload['accountTripCalculator']));
        assert(is_bool($payload['propertyTripCalculators']));
        assert(is_bool($payload['events']));
        assert(is_bool($payload['compensation']));
        assert(is_bool($payload['organizationalCarbonFootprint']));

        $contests = $payload['contests'] ?? false;
        assert(is_bool($contests));

        return new self(
            accountId: new AccountId($payload['accountId']),
            accountTripCalculator: $payload['accountTripCalculator'],
            propertyTripCalculators: $payload['propertyTripCalculators'],
            events: $payload['events'],
            compensation: $payload['compensation'],
            organizationalCarbonFootprint: $payload['organizationalCarbonFootprint'],
            contests: $contests,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
