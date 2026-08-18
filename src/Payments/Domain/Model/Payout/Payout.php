<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Library\Domain\Model\Media\MediaId;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Payout
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private PayoutId $id,
        private ?string $number = null,
        private ?PayeeId $payeeId = null,
        private float $baseAmount = 0.0,
        private float $payableAmount = 0.0,
        private string $currency = 'EUR',
        private int $linesCount = 0,
        private PayoutStatus $status = PayoutStatus::Draft,
        private ?string $gateway = null,
        private ?string $externalId = null,
        private ?string $reference = null,
        private ?string $evidence = null,
        private ?string $failureReason = null,
        private ?UserId $createdBy = null,
        private ?UserId $resolvedBy = null,
        private ?string $notes = null,
        array $events = [],
        private ?\DateTimeImmutable $periodFrom = null,
        private ?\DateTimeImmutable $periodTo = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $sentAt = null,
        private ?\DateTimeImmutable $resolvedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PayoutId
    {
        return $this->id;
    }

    public function number(): ?string
    {
        return $this->number;
    }

    public function payeeId(): ?PayeeId
    {
        return $this->payeeId;
    }

    public function baseAmount(): float
    {
        return $this->baseAmount;
    }

    public function payableAmount(): float
    {
        return $this->payableAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function linesCount(): int
    {
        return $this->linesCount;
    }

    public function status(): PayoutStatus
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

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function evidence(): ?MediaId
    {
        return null !== $this->evidence ? new MediaId($this->evidence) : null;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function createdBy(): ?UserId
    {
        return $this->createdBy;
    }

    public function resolvedBy(): ?UserId
    {
        return $this->resolvedBy;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function periodFrom(): ?\DateTimeImmutable
    {
        return $this->periodFrom;
    }

    public function periodTo(): ?\DateTimeImmutable
    {
        return $this->periodTo;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function isDraft(): bool
    {
        return PayoutStatus::Draft === $this->status;
    }

    public function isPaid(): bool
    {
        return PayoutStatus::Paid === $this->status;
    }

    public function isCancellable(): bool
    {
        return PayoutStatus::Draft === $this->status || PayoutStatus::Failed === $this->status;
    }

    public function isSendable(): bool
    {
        return PayoutStatus::Draft === $this->status || PayoutStatus::Failed === $this->status;
    }

    public function create(
        string $number,
        PayeeId $payeeId,
        float $baseAmount,
        string $currency,
        int $linesCount,
        UserId $createdBy,
        ?\DateTimeImmutable $periodFrom = null,
        ?\DateTimeImmutable $periodTo = null,
        ?string $notes = null,
    ): void {
        if ($baseAmount <= 0.0) {
            throw new \InvalidArgumentException('Payout amount must be greater than zero.');
        }

        if ($linesCount <= 0) {
            throw new \InvalidArgumentException('A payout needs at least one ledger entry.');
        }

        $this->recordApplyAndPublish(
            new PayoutCreated(
                payoutId: $this->id(),
                number: $number,
                payeeId: $payeeId,
                baseAmount: $baseAmount,
                payableAmount: $baseAmount,
                currency: $currency,
                linesCount: $linesCount,
                createdBy: $createdBy,
                periodFrom: $periodFrom,
                periodTo: $periodTo,
                notes: $notes,
            ),
        );
    }

    public function send(PayoutReference $reference): void
    {
        if (!$this->isSendable()) {
            throw new PayoutNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new PayoutSent(
                payoutId: $this->id(),
                gateway: $reference->gateway,
                externalId: $reference->externalId,
            ),
        );
    }

    public function pay(string $reference, ?MediaId $evidence, UserId $resolvedBy): void
    {
        if ($this->status->isResolved()) {
            throw new PayoutNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new PayoutPaid(
                payoutId: $this->id(),
                reference: $reference,
                evidence: $evidence,
                resolvedBy: $resolvedBy,
            ),
        );
    }

    public function fail(string $reason): void
    {
        if ($this->status->isResolved()) {
            throw new PayoutNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new PayoutFailed(
                payoutId: $this->id(),
                reason: $reason,
            ),
        );
    }

    public function cancel(): void
    {
        if (!$this->isCancellable()) {
            throw new PayoutNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(new PayoutCancelled(payoutId: $this->id()));
    }

    public function applyPayoutCreated(PayoutCreated $event): void
    {
        $this->number = $event->number();
        $this->payeeId = $event->payeeId();
        $this->baseAmount = $event->baseAmount();
        $this->payableAmount = $event->payableAmount();
        $this->currency = $event->currency();
        $this->linesCount = $event->linesCount();
        $this->createdBy = $event->createdBy();
        $this->periodFrom = $event->periodFrom();
        $this->periodTo = $event->periodTo();
        $this->notes = $event->notes();
        $this->status = PayoutStatus::Draft;
        $this->createdAt = $event->occurredOn();
    }

    public function applyPayoutSent(PayoutSent $event): void
    {
        $this->status = PayoutStatus::Sent;
        $this->gateway = $event->gateway();
        $this->externalId = $event->externalId();
        $this->failureReason = null;
        $this->sentAt = $event->occurredOn();
    }

    public function applyPayoutPaid(PayoutPaid $event): void
    {
        $this->status = PayoutStatus::Paid;
        $this->reference = $event->reference();
        $this->evidence = null !== $event->evidence() ? (string) $event->evidence() : null;
        $this->resolvedBy = $event->resolvedBy();
        $this->failureReason = null;
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyPayoutFailed(PayoutFailed $event): void
    {
        $this->status = PayoutStatus::Failed;
        $this->failureReason = $event->reason();
    }

    public function applyPayoutCancelled(PayoutCancelled $event): void
    {
        $this->status = PayoutStatus::Cancelled;
        $this->resolvedAt = $event->occurredOn();
    }
}
