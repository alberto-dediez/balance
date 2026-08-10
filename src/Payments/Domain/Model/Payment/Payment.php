<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Shared\Domain\Model\AggregateRoot;

class Payment
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private PaymentId $id,
        private string $subjectType = '',
        private string $subjectId = '',
        private string $description = '',
        private float $amount = 0.0,
        private string $currency = 'EUR',
        private string $customerEmail = '',
        private string $customerName = '',
        private PaymentStatus $status = PaymentStatus::Pending,
        private ?string $gateway = null,
        private ?string $externalId = null,
        private ?string $externalPaymentId = null,
        private ?string $failureReason = null,
        array $events = [],
        private \DateTimeImmutable $startedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $paidAt = null,
        private ?\DateTimeImmutable $resolvedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function subjectType(): string
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function gateway(): ?string
    {
        return $this->gateway;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function externalPaymentId(): ?string
    {
        return $this->externalPaymentId;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function isPaid(): bool
    {
        return PaymentStatus::Paid === $this->status;
    }

    public function isPending(): bool
    {
        return PaymentStatus::Pending === $this->status;
    }

    public function isExpired(): bool
    {
        return PaymentStatus::Expired === $this->status;
    }

    public function start(
        string $subjectType,
        string $subjectId,
        string $description,
        float $amount,
        string $currency,
        string $customerEmail,
        string $customerName,
        string $gateway,
        string $externalId,
    ): void {
        if ($amount <= 0.0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->recordApplyAndPublish(
            new PaymentStarted(
                paymentId: $this->id(),
                subjectType: $subjectType,
                subjectId: $subjectId,
                description: $description,
                amount: $amount,
                currency: $currency,
                customerEmail: $customerEmail,
                customerName: $customerName,
                gateway: $gateway,
                externalId: $externalId,
            ),
        );
    }

    public function succeed(?string $externalPaymentId = null): void
    {
        if ($this->status->isResolved()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PaymentSucceeded(
                paymentId: $this->id(),
                subjectType: $this->subjectType,
                subjectId: $this->subjectId,
                externalPaymentId: $externalPaymentId,
            ),
        );
    }

    public function fail(string $reason): void
    {
        if ($this->status->isResolved()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PaymentFailed(
                paymentId: $this->id(),
                subjectType: $this->subjectType,
                subjectId: $this->subjectId,
                reason: $reason,
            ),
        );
    }

    public function expire(): void
    {
        if ($this->status->isResolved()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PaymentExpired(
                paymentId: $this->id(),
                subjectType: $this->subjectType,
                subjectId: $this->subjectId,
            ),
        );
    }

    public function applyPaymentStarted(PaymentStarted $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->subjectId = $event->subjectId();
        $this->description = $event->description();
        $this->amount = $event->amount();
        $this->currency = $event->currency();
        $this->customerEmail = $event->customerEmail();
        $this->customerName = $event->customerName();
        $this->gateway = $event->gateway();
        $this->externalId = $event->externalId();
        $this->status = PaymentStatus::Pending;
        $this->startedAt = $event->occurredOn();
    }

    public function applyPaymentSucceeded(PaymentSucceeded $event): void
    {
        $this->status = PaymentStatus::Paid;
        $this->externalPaymentId = $event->externalPaymentId();
        $this->paidAt = $event->occurredOn();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyPaymentFailed(PaymentFailed $event): void
    {
        $this->status = PaymentStatus::Failed;
        $this->failureReason = $event->reason();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyPaymentExpired(PaymentExpired $event): void
    {
        $this->status = PaymentStatus::Expired;
        $this->resolvedAt = $event->occurredOn();
    }
}
