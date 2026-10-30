<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PropertyOperatorContactUpdated extends DomainEvent
{
    protected static string $eventName = 'propertyOperatorContactUpdated';

    public function __construct(
        private readonly PropertyId $propertyId,
        private readonly OperatorContact $operatorContact,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function operatorContact(): OperatorContact
    {
        return $this->operatorContact;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyId;
    }

    public function payload(): array
    {
        return [
            'propertyId' => (string) $this->propertyId,
            'operatorEmail' => $this->operatorContact->email(),
            'operatorName' => $this->operatorContact->name(),
            'operatorLocale' => $this->operatorContact->locale()?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PropertyOperatorContactUpdated(
            propertyId: new PropertyId($payload['propertyId']),
            operatorContact: OperatorContact::from($payload['operatorEmail'] ?? null, $payload['operatorName'] ?? null, Locale::tryFrom($payload['operatorLocale'] ?? '')),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
