<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Waste\WasteId;

class Alert implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private AlertId $id,
        private AlertType $type = AlertType::NoMeasurements,
        private ?PropertyId $propertyId = null,
        private ?WasteId $wasteId = null,
        private ?AccountId $accountId = null,
        private AlertStatus $status = AlertStatus::New,
        private \DateTimeImmutable $detectedAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $periodFrom = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $periodTo = new \DateTimeImmutable('NOW'),
        private ?float $value = null,
        private ?float $threshold = null,
        private AlertPriority $priority = AlertPriority::Info,
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): AlertId
    {
        return $this->id;
    }

    public function type(): AlertType
    {
        return $this->type;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId(): ?WasteId
    {
        return $this->wasteId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function status(): AlertStatus
    {
        return $this->status;
    }

    public function detectedAt(): \DateTimeImmutable
    {
        return $this->detectedAt;
    }

    public function periodFrom(): \DateTimeImmutable
    {
        return $this->periodFrom;
    }

    public function periodTo(): \DateTimeImmutable
    {
        return $this->periodTo;
    }

    public function singleDayPeriod(): bool
    {
        return $this->periodFrom()->format('Y-m-d') === $this->periodTo()->format('Y-m-d');
    }

    public function value(): ?float
    {
        return $this->value;
    }

    public function threshold(): ?float
    {
        return $this->threshold;
    }

    public function priority(): AlertPriority
    {
        return $this->priority;
    }

    public function detect(
        AlertType $type,
        ?PropertyId $propertyId,
        ?WasteId $wasteId,
        AccountId $accountId,
        \DateTimeImmutable $detectedAt,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?float $value,
        ?float $threshold,
        AlertPriority $priority = AlertPriority::Info,
    ): void {
        $this->recordApplyAndPublish(
            new AlertDetected(
                alertId: $this->id(),
                type: $type,
                propertyId: $propertyId,
                wasteId: $wasteId,
                accountId: $accountId,
                detectedAt: $detectedAt,
                periodFrom: $from,
                periodTo: $to,
                value: $value,
                threshold: $threshold,
                priority: $priority,
            ),
        );
    }

    public function acknowledge(): void
    {
        $this->recordApplyAndPublish(
            new AlertAcknowledged(
                alertId: $this->id(),
            ),
        );
    }

    public function applyAlertDetected(AlertDetected $event): void
    {
        $this->type = $event->type();
        $this->propertyId = $event->propertyId();
        $this->wasteId = $event->wasteId();
        $this->accountId = $event->accountId();
        $this->status = AlertStatus::New;
        $this->detectedAt = $event->detectedAt();
        $this->periodFrom = $event->periodFrom();
        $this->periodTo = $event->periodTo();
        $this->value = $event->value();
        $this->threshold = $event->threshold();
        $this->priority = $event->priority();
    }

    public function applyAlertAcknowledged(AlertAcknowledged $event): void
    {
        $this->status = AlertStatus::Acknowledged;
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
