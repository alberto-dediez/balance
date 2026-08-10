<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PaymentFailed extends DomainEvent
{
    protected static string $eventName = 'paymentFailed';

    public function __construct(
        private PaymentId $paymentId,
        private string $subjectType,
        private string $subjectId,
        private string $reason,
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

    public function reason(): string
    {
        return $this->reason;
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
            'reason' => $this->reason,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PaymentFailed(
            paymentId: new PaymentId($payload['paymentId']),
            subjectType: (string) $payload['subjectType'],
            subjectId: (string) $payload['subjectId'],
            reason: (string) $payload['reason'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
