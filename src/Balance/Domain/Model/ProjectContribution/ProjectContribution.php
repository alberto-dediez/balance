<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class ProjectContribution
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private ProjectContributionId $id,
        private ?AccountId $accountId = null,
        private ?ProjectId $projectId = null,
        private ProjectContributionOrigin $origin = ProjectContributionOrigin::PropertyTripCalculation,
        private ?string $originId = null,
        private ?float $co2eqKg = null,
        private float $tonnes = 0.0,
        private float $pricePerTonne = 0.0,
        private ?float $costPerTonne = null,
        private bool $freeAmount = false,
        private float $contributionAmount = 0.0,
        private float $managementFeeAmount = 0.0,
        private float $taxableAmount = 0.0,
        private float $vatRate = 0.0,
        private float $vatAmount = 0.0,
        private float $totalAmount = 0.0,
        private ?VatExemptionReason $vatReason = null,
        private string $currency = 'EUR',
        private string $contributorName = '',
        private string $contributorEmail = '',
        private string $contributorCountry = '',
        private bool $contributorIsCompany = false,
        private ?string $contributorCompanyName = null,
        private ?string $contributorVatNumber = null,
        private ProjectContributionStatus $status = ProjectContributionStatus::Pending,
        private ?PaymentId $paymentId = null,
        private ?string $failureReason = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $expiresAt = null,
        private ?\DateTimeImmutable $paidAt = null,
        private ?\DateTimeImmutable $resolvedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ProjectContributionId
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

    public function origin(): ProjectContributionOrigin
    {
        return $this->origin;
    }

    public function originId(): ?string
    {
        return $this->originId;
    }

    public function co2eqKg(): ?float
    {
        return $this->co2eqKg;
    }

    public function tonnes(): float
    {
        return $this->tonnes;
    }

    public function pricePerTonne(): float
    {
        return $this->pricePerTonne;
    }

    public function costPerTonne(): ?float
    {
        return $this->costPerTonne;
    }

    public function isFreeAmount(): bool
    {
        return $this->freeAmount;
    }

    public function contributionAmount(): float
    {
        return $this->contributionAmount;
    }

    public function managementFeeAmount(): float
    {
        return $this->managementFeeAmount;
    }

    public function taxableAmount(): float
    {
        return $this->taxableAmount;
    }

    public function vatRate(): float
    {
        return $this->vatRate;
    }

    public function vatAmount(): float
    {
        return $this->vatAmount;
    }

    public function totalAmount(): float
    {
        return $this->totalAmount;
    }

    public function vatReason(): ?VatExemptionReason
    {
        return $this->vatReason;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function contributorName(): string
    {
        return $this->contributorName;
    }

    public function contributorEmail(): string
    {
        return $this->contributorEmail;
    }

    public function contributorCountry(): string
    {
        return $this->contributorCountry;
    }

    public function contributorIsCompany(): bool
    {
        return $this->contributorIsCompany;
    }

    public function contributorCompanyName(): ?string
    {
        return $this->contributorCompanyName;
    }

    public function contributorVatNumber(): ?string
    {
        return $this->contributorVatNumber;
    }

    public function status(): ProjectContributionStatus
    {
        return $this->status;
    }

    public function paymentId(): ?PaymentId
    {
        return $this->paymentId;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function resolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function isPending(): bool
    {
        return ProjectContributionStatus::Pending === $this->status;
    }

    public function isPaid(): bool
    {
        return ProjectContributionStatus::Paid === $this->status;
    }

    /**
     * A contribution holds tonnes back from the account balance while it is paid,
     * or still pending and within its reservation window.
     */
    public function reservesCredit(\DateTimeImmutable $now): bool
    {
        if ($this->isPaid()) {
            return true;
        }

        return $this->isPending() && $this->expiresAt instanceof \DateTimeImmutable && $this->expiresAt > $now;
    }

    public function hasExpired(\DateTimeImmutable $now): bool
    {
        return $this->isPending() && $this->expiresAt instanceof \DateTimeImmutable && $this->expiresAt <= $now;
    }

    public function register(
        AccountId $accountId,
        ProjectId $projectId,
        ProjectContributionOrigin $origin,
        ?string $originId,
        ?float $co2eqKg,
        float $tonnes,
        float $pricePerTonne,
        ?float $costPerTonne,
        bool $freeAmount,
        float $contributionAmount,
        float $managementFeeAmount,
        float $taxableAmount,
        float $vatRate,
        float $vatAmount,
        float $totalAmount,
        VatExemptionReason $vatReason,
        string $currency,
        string $contributorName,
        string $contributorEmail,
        string $contributorCountry,
        bool $contributorIsCompany,
        ?string $contributorCompanyName,
        ?string $contributorVatNumber,
        \DateTimeImmutable $expiresAt,
        ?PaymentId $paymentId = null,
    ): void {
        if ($tonnes <= 0.0) {
            throw new \InvalidArgumentException('Contribution tonnes must be greater than zero.');
        }

        if ($totalAmount <= 0.0) {
            throw new \InvalidArgumentException('Contribution total amount must be greater than zero.');
        }

        $this->recordApplyAndPublish(
            new ProjectContributionRegistered(
                projectContributionId: $this->id(),
                accountId: $accountId,
                projectId: $projectId,
                origin: $origin,
                originId: $originId,
                co2eqKg: $co2eqKg,
                tonnes: $tonnes,
                pricePerTonne: $pricePerTonne,
                costPerTonne: $costPerTonne,
                freeAmount: $freeAmount,
                contributionAmount: $contributionAmount,
                managementFeeAmount: $managementFeeAmount,
                taxableAmount: $taxableAmount,
                vatRate: $vatRate,
                vatAmount: $vatAmount,
                totalAmount: $totalAmount,
                vatReason: $vatReason,
                currency: $currency,
                contributorName: $contributorName,
                contributorEmail: $contributorEmail,
                contributorCountry: $contributorCountry,
                contributorIsCompany: $contributorIsCompany,
                contributorCompanyName: $contributorCompanyName,
                contributorVatNumber: $contributorVatNumber,
                expiresAt: $expiresAt,
                paymentId: $paymentId,
            ),
        );
    }

    /** The reservation is written before the gateway is called, so the payment is attached afterwards. */
    public function linkPayment(PaymentId $paymentId): void
    {
        if ($this->paymentId instanceof PaymentId && $this->paymentId->equals($paymentId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProjectContributionPaymentLinked(
                projectContributionId: $this->id(),
                paymentId: $paymentId,
            ),
        );
    }

    public function pay(): void
    {
        if ($this->status->isResolved()) {
            // Paying twice is routine (webhook, return and cron all confirm), but paying something
            // already expired or failed means money came in without a reservation behind it.
            if (!$this->isPaid()) {
                $this->recordApplyAndPublish(
                    new ProjectContributionPaymentUnreconciled(
                        projectContributionId: $this->id(),
                        status: $this->status,
                        paymentId: $this->paymentId,
                    ),
                );
            }

            return;
        }

        $this->recordApplyAndPublish(
            new ProjectContributionPaid(
                projectContributionId: $this->id(),
                accountId: $this->accountId,
                projectId: $this->projectId,
                tonnes: $this->tonnes,
            ),
        );
    }

    public function fail(string $reason): void
    {
        if ($this->status->isResolved()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProjectContributionFailed(
                projectContributionId: $this->id(),
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
            new ProjectContributionExpired(
                projectContributionId: $this->id(),
            ),
        );
    }

    public function applyProjectContributionRegistered(ProjectContributionRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->projectId = $event->projectId();
        $this->origin = $event->origin();
        $this->originId = $event->originId();
        $this->co2eqKg = $event->co2eqKg();
        $this->tonnes = $event->tonnes();
        $this->pricePerTonne = $event->pricePerTonne();
        $this->costPerTonne = $event->costPerTonne();
        $this->freeAmount = $event->freeAmount();
        $this->contributionAmount = $event->contributionAmount();
        $this->managementFeeAmount = $event->managementFeeAmount();
        $this->taxableAmount = $event->taxableAmount();
        $this->vatRate = $event->vatRate();
        $this->vatAmount = $event->vatAmount();
        $this->totalAmount = $event->totalAmount();
        $this->vatReason = $event->vatReason();
        $this->currency = $event->currency();
        $this->contributorName = $event->contributorName();
        $this->contributorEmail = $event->contributorEmail();
        $this->contributorCountry = $event->contributorCountry();
        $this->contributorIsCompany = $event->contributorIsCompany();
        $this->contributorCompanyName = $event->contributorCompanyName();
        $this->contributorVatNumber = $event->contributorVatNumber();
        $this->paymentId = $event->paymentId();
        $this->status = ProjectContributionStatus::Pending;
        $this->registeredAt = $event->occurredOn();
        $this->expiresAt = $event->expiresAt();
    }

    public function applyProjectContributionPaymentLinked(ProjectContributionPaymentLinked $event): void
    {
        $this->paymentId = $event->paymentId();
    }

    public function applyProjectContributionPaid(ProjectContributionPaid $event): void
    {
        $this->status = ProjectContributionStatus::Paid;
        $this->paidAt = $event->occurredOn();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyProjectContributionFailed(ProjectContributionFailed $event): void
    {
        $this->status = ProjectContributionStatus::Failed;
        $this->failureReason = $event->reason();
        $this->resolvedAt = $event->occurredOn();
    }

    public function applyProjectContributionExpired(ProjectContributionExpired $event): void
    {
        $this->status = ProjectContributionStatus::Expired;
        $this->resolvedAt = $event->occurredOn();
    }

    /** The contribution keeps its resolved state: the event only reports the mismatch. */
    public function applyProjectContributionPaymentUnreconciled(ProjectContributionPaymentUnreconciled $event): void {}
}
