<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class LedgerEntry
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private LedgerEntryId $id,
        private LedgerEntryType $type = LedgerEntryType::ContributionNet,
        private LedgerBeneficiary $beneficiary = LedgerBeneficiary::Bioscore,
        private ?string $beneficiaryId = null,
        private float $amount = 0.0,
        private string $currency = 'EUR',
        private ?ProjectContributionId $projectContributionId = null,
        private ?ProjectId $projectId = null,
        private ?CreditPurchaseId $creditPurchaseId = null,
        private ?float $tonnes = null,
        private ?LedgerEntryId $reversesEntryId = null,
        private ?string $concept = null,
        private ?UserId $recordedBy = null,
        private ?PayoutId $payoutId = null,
        array $events = [],
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $settledAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): LedgerEntryId
    {
        return $this->id;
    }

    public function type(): LedgerEntryType
    {
        return $this->type;
    }

    public function beneficiary(): LedgerBeneficiary
    {
        return $this->beneficiary;
    }

    public function beneficiaryId(): ?string
    {
        return $this->beneficiaryId;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function projectContributionId(): ?ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function creditPurchaseId(): ?CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function tonnes(): ?float
    {
        return $this->tonnes;
    }

    public function reversesEntryId(): ?LedgerEntryId
    {
        return $this->reversesEntryId;
    }

    public function concept(): ?string
    {
        return $this->concept;
    }

    public function recordedBy(): ?UserId
    {
        return $this->recordedBy;
    }

    public function payoutId(): ?PayoutId
    {
        return $this->payoutId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function settledAt(): ?\DateTimeImmutable
    {
        return $this->settledAt;
    }

    public function isSettled(): bool
    {
        return $this->payoutId instanceof PayoutId;
    }

    public function isSettleable(): bool
    {
        return !$this->isSettled()
            && $this->beneficiary->isSettleable()
            && null !== $this->beneficiaryId
            && 0.0 !== $this->amount;
    }

    public function belongsTo(LedgerBeneficiary $beneficiary, ?string $beneficiaryId): bool
    {
        return $this->beneficiary === $beneficiary && $this->beneficiaryId === $beneficiaryId;
    }

    public function recordEntry(
        LedgerEntryType $type,
        LedgerBeneficiary $beneficiary,
        ?string $beneficiaryId,
        float $amount,
        string $currency,
        ?ProjectContributionId $projectContributionId = null,
        ?ProjectId $projectId = null,
        ?CreditPurchaseId $creditPurchaseId = null,
        ?float $tonnes = null,
        ?LedgerEntryId $reversesEntryId = null,
        ?string $concept = null,
        ?UserId $recordedBy = null,
        ?\DateTimeImmutable $occurredAt = null,
    ): void {
        if ($beneficiary->isSettleable() && (null === $beneficiaryId || '' === $beneficiaryId)) {
            throw new \InvalidArgumentException('A settleable ledger entry requires a beneficiary id.');
        }

        if (LedgerEntryType::Adjustment === $type && (null === $concept || '' === trim($concept))) {
            throw new \InvalidArgumentException('An adjustment ledger entry requires a concept.');
        }

        $this->recordApplyAndPublish(
            new LedgerEntryRecorded(
                ledgerEntryId: $this->id(),
                type: $type,
                beneficiary: $beneficiary,
                beneficiaryId: $beneficiaryId,
                amount: $amount,
                currency: $currency,
                projectContributionId: $projectContributionId,
                projectId: $projectId,
                creditPurchaseId: $creditPurchaseId,
                tonnes: $tonnes,
                reversesEntryId: $reversesEntryId,
                concept: $concept,
                recordedBy: $recordedBy,
                occurredOn: $occurredAt,
            ),
        );
    }

    public function settle(PayoutId $payoutId): void
    {
        if (!$this->isSettleable()) {
            throw new UnsettleableLedgerEntryException($this->id());
        }

        $this->recordApplyAndPublish(
            new LedgerEntrySettled(
                ledgerEntryId: $this->id(),
                payoutId: $payoutId,
            ),
        );
    }

    public function unsettle(): void
    {
        if (!$this->isSettled()) {
            return;
        }

        $this->recordApplyAndPublish(
            new LedgerEntryUnsettled(
                ledgerEntryId: $this->id(),
            ),
        );
    }

    public function applyLedgerEntryRecorded(LedgerEntryRecorded $event): void
    {
        $this->type = $event->type();
        $this->beneficiary = $event->beneficiary();
        $this->beneficiaryId = $event->beneficiaryId();
        $this->amount = $event->amount();
        $this->currency = $event->currency();
        $this->projectContributionId = $event->projectContributionId();
        $this->projectId = $event->projectId();
        $this->creditPurchaseId = $event->creditPurchaseId();
        $this->tonnes = $event->tonnes();
        $this->reversesEntryId = $event->reversesEntryId();
        $this->concept = $event->concept();
        $this->recordedBy = $event->recordedBy();
        $this->occurredAt = $event->occurredOn();
    }

    public function applyLedgerEntrySettled(LedgerEntrySettled $event): void
    {
        $this->payoutId = $event->payoutId();
        $this->settledAt = $event->occurredOn();
    }

    public function applyLedgerEntryUnsettled(LedgerEntryUnsettled $event): void
    {
        $this->payoutId = null;
        $this->settledAt = null;
    }
}
