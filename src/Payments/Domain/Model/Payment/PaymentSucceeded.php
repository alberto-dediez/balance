<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PaymentSucceeded extends DomainEvent
{
    protected static string $eventName = 'paymentSucceeded';

    public function __construct(
        private PaymentId $paymentId,
        private string $subjectType,
        private string $subjectId,
        private ?string $externalPaymentId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function subjectType(): string
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function externalPaymentId(): ?string
    {
        return $this->externalPaymentId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->paymentId();
    }

    public function payload(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'subjectType' => $this->subjectType,
            'subjectId' => $this->subjectId,
            'externalPaymentId' => $this->externalPaymentId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PaymentSucceeded(
            paymentId: new PaymentId($payload['paymentId']),
            subjectType: (string) $payload['subjectType'],
            subjectId: (string) $payload['subjectId'],
            externalPaymentId: null !== $payload['externalPaymentId'] ? (string) $payload['externalPaymentId'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
