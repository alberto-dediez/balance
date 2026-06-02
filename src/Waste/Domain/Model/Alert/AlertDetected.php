<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;

final class AlertDetected extends DomainEvent
{
    protected static string $eventName = 'alertDetected';

    public function __construct(
        private readonly AlertId $alertId,
        private readonly AlertType $type,
        private readonly ?PropertyId $propertyId,
        private readonly ?WasteId $wasteId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $detectedAt,
        private readonly \DateTimeImmutable $periodFrom,
        private readonly \DateTimeImmutable $periodTo,
        private readonly ?float $value,
        private readonly ?float $threshold,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function alertId(): AlertId
    {
        return $this->alertId;
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

    public function accountId(): AccountId
    {
        return $this->accountId;
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

    public function value(): ?float
    {
        return $this->value;
    }

    public function threshold(): ?float
    {
        return $this->threshold;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->alertId;
    }

    public function payload(): array
    {
        return [
            'alertId' => (string) $this->alertId,
            'type' => $this->type->value,
            'propertyId' => null !== $this->propertyId ? (string) $this->propertyId : null,
            'wasteId' => null !== $this->wasteId ? (string) $this->wasteId : null,
            'accountId' => (string) $this->accountId,
            'detectedAt' => $this->detectedAt->format('Y-m-d H:i:s'),
            'periodFrom' => $this->periodFrom->format('Y-m-d'),
            'periodTo' => $this->periodTo->format('Y-m-d'),
            'value' => $this->value,
            'threshold' => $this->threshold,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $type = AlertType::from($payload['type']);

        return new AlertDetected(
            alertId: new AlertId($payload['alertId']),
            type: $type,
            propertyId: isset($payload['propertyId']) && is_string($payload['propertyId']) ? new PropertyId($payload['propertyId']) : null,
            wasteId: isset($payload['wasteId']) && is_string($payload['wasteId']) ? new WasteId($payload['wasteId']) : null,
            accountId: new AccountId($payload['accountId']),
            detectedAt: new \DateTimeImmutable($payload['detectedAt']),
            periodFrom: new \DateTimeImmutable($payload['periodFrom']),
            periodTo: new \DateTimeImmutable($payload['periodTo']),
            value: isset($payload['value']) ? (float) $payload['value'] : null,
            threshold: isset($payload['threshold']) ? (float) $payload['threshold'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
