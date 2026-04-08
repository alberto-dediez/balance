<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class IncidenceUpdated extends DomainEvent
{
    protected static string $eventName = 'incidenceUpdated';

    public function __construct(
        private IncidenceId $incidenceId,
        private string $name,
        private \DateTimeImmutable $exceptionOccurredOn,
        private IncidenceType $incidenceType,
        private CriticalPointId $originId,
        private string $description,
        private string $effect,
        private PropertyId $propertyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function incidenceId(): IncidenceId
    {
        return $this->incidenceId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function exceptionOccurredOn(): \DateTimeImmutable
    {
        return $this->exceptionOccurredOn;
    }

    public function incidenceType(): IncidenceType
    {
        return $this->incidenceType;
    }

    public function originId(): CriticalPointId
    {
        return $this->originId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function effect(): string
    {
        return $this->effect;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->incidenceId();
    }

    public function payload(): array
    {
        return [
            'incidenceId' => (string) $this->incidenceId,
            'name' => $this->name,
            'exceptionOccurredOn' => $this->exceptionOccurredOn->format(\DateTimeInterface::ATOM),
            'incidenceType' => $this->incidenceType->value,
            'originId' => (string) $this->originId,
            'description' => $this->description,
            'effect' => $this->effect,
            'propertyId' => (string) $this->propertyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            incidenceId: new IncidenceId($payload['incidenceId']),
            name: $payload['name'],
            exceptionOccurredOn: new \DateTimeImmutable($payload['exceptionOccurredOn']),
            incidenceType: IncidenceType::from($payload['incidenceType']),
            originId: new CriticalPointId($payload['originId']),
            description: $payload['description'],
            effect: $payload['effect'],
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
