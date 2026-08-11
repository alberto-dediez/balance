<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ProjectContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'projectContributionRegistered';

    public function __construct(
        private ProjectContributionId $projectContributionId,
        private AccountId $accountId,
        private ProjectId $projectId,
        private ProjectContributionOrigin $origin,
        private ?string $originId,
        private ?float $co2eqKg,
        private float $tonnes,
        private float $pricePerTonne,
        private ?float $costPerTonne,
        private bool $freeAmount,
        private float $contributionAmount,
        private float $managementFeeAmount,
        private float $taxableAmount,
        private float $vatRate,
        private float $vatAmount,
        private float $totalAmount,
        private VatExemptionReason $vatReason,
        private string $currency,
        private string $contributorName,
        private string $contributorEmail,
        private string $contributorCountry,
        private bool $contributorIsCompany,
        private ?string $contributorCompanyName,
        private ?string $contributorVatNumber,
        private Locale $locale,
        private \DateTimeImmutable $expiresAt,
        private ?PaymentId $paymentId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectContributionId(): ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ProjectId
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

    public function freeAmount(): bool
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

    public function vatReason(): VatExemptionReason
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

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function paymentId(): ?PaymentId
    {
        return $this->paymentId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectContributionId();
    }

    public function payload(): array
    {
        return [
            'projectContributionId' => (string) $this->projectContributionId,
            'accountId' => (string) $this->accountId,
            'projectId' => (string) $this->projectId,
            'origin' => $this->origin->value,
            'originId' => $this->originId,
            'co2eqKg' => $this->co2eqKg,
            'tonnes' => $this->tonnes,
            'pricePerTonne' => $this->pricePerTonne,
            'costPerTonne' => $this->costPerTonne,
            'freeAmount' => $this->freeAmount,
            'contributionAmount' => $this->contributionAmount,
            'managementFeeAmount' => $this->managementFeeAmount,
            'taxableAmount' => $this->taxableAmount,
            'vatRate' => $this->vatRate,
            'vatAmount' => $this->vatAmount,
            'totalAmount' => $this->totalAmount,
            'vatReason' => $this->vatReason->value,
            'currency' => $this->currency,
            'contributorName' => $this->contributorName,
            'contributorEmail' => $this->contributorEmail,
            'contributorCountry' => $this->contributorCountry,
            'contributorIsCompany' => $this->contributorIsCompany,
            'contributorCompanyName' => $this->contributorCompanyName,
            'contributorVatNumber' => $this->contributorVatNumber,
            'locale' => $this->locale->value,
            'expiresAt' => $this->expiresAt->format(\DateTimeInterface::ATOM),
            'paymentId' => null !== $this->paymentId ? (string) $this->paymentId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProjectContributionRegistered(
            projectContributionId: new ProjectContributionId($payload['projectContributionId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            origin: ProjectContributionOrigin::from($payload['origin']),
            originId: null !== $payload['originId'] ? (string) $payload['originId'] : null,
            co2eqKg: null !== $payload['co2eqKg'] ? (float) $payload['co2eqKg'] : null,
            tonnes: (float) $payload['tonnes'],
            pricePerTonne: (float) $payload['pricePerTonne'],
            costPerTonne: null !== $payload['costPerTonne'] ? (float) $payload['costPerTonne'] : null,
            freeAmount: (bool) $payload['freeAmount'],
            contributionAmount: (float) $payload['contributionAmount'],
            managementFeeAmount: (float) $payload['managementFeeAmount'],
            taxableAmount: (float) $payload['taxableAmount'],
            vatRate: (float) $payload['vatRate'],
            vatAmount: (float) $payload['vatAmount'],
            totalAmount: (float) $payload['totalAmount'],
            vatReason: VatExemptionReason::from($payload['vatReason']),
            currency: (string) $payload['currency'],
            contributorName: (string) $payload['contributorName'],
            contributorEmail: (string) $payload['contributorEmail'],
            contributorCountry: (string) $payload['contributorCountry'],
            contributorIsCompany: (bool) $payload['contributorIsCompany'],
            contributorCompanyName: null !== $payload['contributorCompanyName'] ? (string) $payload['contributorCompanyName'] : null,
            contributorVatNumber: null !== $payload['contributorVatNumber'] ? (string) $payload['contributorVatNumber'] : null,
            // Contributions registered before the language was recorded replay in the default one.
            locale: Locale::tryFrom((string) ($payload['locale'] ?? '')) ?? Locale::Spanish,
            expiresAt: new \DateTimeImmutable($payload['expiresAt']),
            paymentId: null !== $payload['paymentId'] ? new PaymentId($payload['paymentId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
