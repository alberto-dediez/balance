<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class CreditPurchase
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private CreditPurchaseId $id,
        private ?AccountId $accountId = null,
        private ?ProjectId $projectId = null,
        private ?UserId $requestedBy = null,
        private float $requestedTonnes = 0.0,
        private CreditPurchaseStatus $status = CreditPurchaseStatus::Pending,
        private ?float $purchasedTonnes = null,
        private ?float $amount = null,
        private ?CreditPurchasePaymentType $paymentType = null,
        private ?string $reference = null,
        private ?string $certificate = null,
        private ?UserId $resolvedBy = null,
        private ?string $rejectionReason = null,
        array $events = [],
        private \DateTimeImmutable $requestedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $resolvedAt = null,
        private ?\DateTimeImmutable $cancelledAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): CreditPurchaseId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function requestedBy(): ?UserId
    {
        return $this->requestedBy;
    }

    public function requestedTonnes(): float
    {
        return $this->requestedTonnes;
    }

    public function status(): CreditPurchaseStatus
    {
        return $this->status;
    }

    public function purchasedTonnes(): ?float
    {
        return $this->purchasedTonnes;
    }

    public function amount(): ?float
    {
        return $this->amount;
    }

    public function pricePerTonne(): ?float
    {
        if (null === $this->amount || null === $this->purchasedTonnes || 0.0 === $this->purchasedTonnes) {
            return null;
        }

        return round($this->amount / $this->purchasedTonnes, 6);
    }

    public function paymentType(): ?CreditPurchasePaymentType
    {
        return $this->paymentType;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function certificate(): ?MediaId
    {
        return null !== $this->certificate ? new MediaId($this->certificate) : null;
    }

    public function resolvedBy(): ?UserId
    {
        return $this->resolvedBy;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function requestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function cancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function isPartiallyFulfilled(): bool
    {
        return CreditPurchaseStatus::Validated === $this->status
            && null !== $this->purchasedTonnes
            && $this->purchasedTonnes < $this->requestedTonnes;
    }

    public function availableTonnes(): float
    {
        if (CreditPurchaseStatus::Validated !== $this->status || null === $this->purchasedTonnes) {
            return 0.0;
        }

        return $this->purchasedTonnes;
    }

    public function request(AccountId $accountId, ProjectId $projectId, UserId $requestedBy, float $tonnes): void
    {
        if ($tonnes <= 0.0) {
            throw new \InvalidArgumentException('Requested tonnes must be greater than zero.');
        }

        $this->recordApplyAndPublish(
            new CreditPurchaseRequested(
                creditPurchaseId: $this->id(),
                accountId: $accountId,
                projectId: $projectId,
                requestedBy: $requestedBy,
                requestedTonnes: $tonnes,
            ),
        );
    }

    public function validate(
        float $purchasedTonnes,
        CreditPurchasePaymentType $paymentType,
        string $reference,
        ?MediaId $certificate,
        UserId $validatedBy,
        ?float $amount = null,
    ): void {
        if (CreditPurchaseStatus::Pending !== $this->status) {
            throw new CreditPurchaseNotPendingException($this->id());
        }

        if ($purchasedTonnes <= 0.0) {
            throw new \InvalidArgumentException('Purchased tonnes must be greater than zero.');
        }

        if (null !== $amount && $amount < 0.0) {
            throw new \InvalidArgumentException('Purchase amount cannot be negative.');
        }

        $this->recordApplyAndPublish(
            new CreditPurchaseValidated(
                creditPurchaseId: $this->id(),
                requestedTonnes: $this->requestedTonnes,
                purchasedTonnes: $purchasedTonnes,
                amount: $amount,
                paymentType: $paymentType,
                reference: $reference,
                certificate: $certificate,
                validatedBy: $validatedBy,
            ),
        );
    }

    public function reject(string $reason, UserId $rejectedBy): void
    {
        if (CreditPurchaseStatus::Pending !== $this->status) {
            throw new CreditPurchaseNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new CreditPurchaseRejected(
                creditPurchaseId: $this->id(),
                reason: $reason,
                rejectedBy: $rejectedBy,
            ),
        );
    }

    public function cancel(): void
    {
        if (CreditPurchaseStatus::Pending !== $this->status) {
            throw new CreditPurchaseNotPendingException($this->id());
        }

        $this->recordApplyAndPublish(
            new CreditPurchaseCancelled(
                creditPurchaseId: $this->id(),
            ),
        );
    }

    public function applyCreditPurchaseRequested(CreditPurchaseRequested $event): void
    {
        $this->accountId = $event->accountId();
        $this->projectId = $event->projectId();
        $this->requestedBy = $event->requestedBy();
        $this->requestedTonnes = $event->requestedTonnes();
        $this->status = CreditPurchaseStatus::Pending;
        $this->requestedAt = $event->occurredOn();
    }

    public function applyCreditPurchaseValidated(CreditPurchaseValidated $event): void
    {
        $this->status = CreditPurchaseStatus::Validated;
        $this->purchasedTonnes = $event->purchasedTonnes();
        $this->amount = $event->amount();
        $this->paymentType = $event->paymentType();
        $this->reference = $event->reference();
        $this->certificate = null !== $event->certificate() ? (string) $event->certificate() : null;
        $this->resolvedBy = $event->validatedBy();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyCreditPurchaseRejected(CreditPurchaseRejected $event): void
    {
        $this->status = CreditPurchaseStatus::Rejected;
        $this->rejectionReason = $event->reason();
        $this->resolvedBy = $event->rejectedBy();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyCreditPurchaseCancelled(CreditPurchaseCancelled $event): void
    {
        $this->status = CreditPurchaseStatus::Cancelled;
        $this->cancelledAt = $event->occurredOn();
    }
}
