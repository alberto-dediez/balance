<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DeliveryLogSignatureStarted extends DomainEvent
{
    protected static string $eventName = 'deliveryLogSignatureStarted';

    public function __construct(
        private readonly DeliveryLogId $deliveryLogId,
        private readonly SignatureRequestId $signatureRequestId,
        private readonly \DateTimeImmutable $deliveredAt,
        private readonly ?PickupType $pickupType = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function deliveryLogId(): DeliveryLogId
    {
        return $this->deliveryLogId;
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function deliveredAt(): \DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function pickupType(): ?PickupType
    {
        return $this->pickupType;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->deliveryLogId;
    }

    public function payload(): array
    {
        return [
            'deliveryLogId' => (string) $this->deliveryLogId,
            'signatureRequestId' => (string) $this->signatureRequestId,
            'deliveredAt' => $this->deliveredAt->format(\DateTimeInterface::ATOM),
            'pickupType' => $this->pickupType?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DeliveryLogSignatureStarted(
            deliveryLogId: new DeliveryLogId($payload['deliveryLogId']),
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            deliveredAt: new \DateTimeImmutable($payload['deliveredAt']),
            pickupType: isset($payload['pickupType']) ? PickupType::from($payload['pickupType']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
