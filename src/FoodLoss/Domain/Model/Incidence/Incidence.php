<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class Incidence implements \Stringable
{
    use AggregateRoot;

    private \DateTimeImmutable $exceptionOccurredOn;
    private IncidenceType $incidenceType = IncidenceType::MachineryFailure;
    private ?CriticalPointId $originId = null;
    private ?PropertyId $propertyId = null;
    private ?AccountId $accountId = null;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private IncidenceId $id,
        private string $name = '',
        ?\DateTimeImmutable $exceptionOccurredOn = null,
        ?IncidenceType $incidenceType = null,
        ?CriticalPointId $originId = null,
        private string $description = '',
        private string $effect = '',
        ?PropertyId $propertyId = null,
        ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
        $this->exceptionOccurredOn = $exceptionOccurredOn ?? new \DateTimeImmutable('NOW');
        if (null !== $incidenceType) {
            $this->incidenceType = $incidenceType;
        }
        $this->originId = $originId;
        $this->propertyId = $propertyId;
        $this->accountId = $accountId;
    }

    public function id(): IncidenceId
    {
        return $this->id;
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

    public function originId(): ?CriticalPointId
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

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        \DateTimeImmutable $exceptionOccurredOn,
        IncidenceType $incidenceType,
        CriticalPointId $originId,
        string $description,
        string $effect,
        PropertyId $propertyId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new IncidenceRegistered(
                incidenceId: $this->id(),
                name: $name,
                exceptionOccurredOn: $exceptionOccurredOn,
                incidenceType: $incidenceType,
                originId: $originId,
                description: $description,
                effect: $effect,
                propertyId: $propertyId,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        \DateTimeImmutable $exceptionOccurredOn,
        IncidenceType $incidenceType,
        CriticalPointId $originId,
        string $description,
        string $effect,
        PropertyId $propertyId,
    ): void {
        $this->recordApplyAndPublish(
            new IncidenceUpdated(
                incidenceId: $this->id(),
                name: $name,
                exceptionOccurredOn: $exceptionOccurredOn,
                incidenceType: $incidenceType,
                originId: $originId,
                description: $description,
                effect: $effect,
                propertyId: $propertyId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new IncidenceRemoved(
                incidenceId: $this->id(),
            ),
        );
    }

    public function applyIncidenceRegistered(IncidenceRegistered $event): void
    {
        $this->name = $event->name();
        $this->exceptionOccurredOn = $event->exceptionOccurredOn();
        $this->incidenceType = $event->incidenceType();
        $this->originId = $event->originId();
        $this->description = $event->description();
        $this->effect = $event->effect();
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyIncidenceUpdated(IncidenceUpdated $event): void
    {
        $this->name = $event->name();
        $this->exceptionOccurredOn = $event->exceptionOccurredOn();
        $this->incidenceType = $event->incidenceType();
        $this->originId = $event->originId();
        $this->description = $event->description();
        $this->effect = $event->effect();
        $this->propertyId = $event->propertyId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyIncidenceRemoved(IncidenceRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
