<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseRequested extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseRequested';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private AccountId $accountId,
        private ProjectId $projectId,
        private UserId $requestedBy,
        private float $requestedTonnes,
        private ?CreditPurchaseQuote $quote = null,
        private ?CreditPurchaseBilling $billing = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function requestedBy(): UserId
    {
        return $this->requestedBy;
    }

    public function requestedTonnes(): float
    {
        return $this->requestedTonnes;
    }

    public function quote(): ?CreditPurchaseQuote
    {
        return $this->quote;
    }

    public function billing(): ?CreditPurchaseBilling
    {
        return $this->billing;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
            'accountId' => (string) $this->accountId,
            'projectId' => (string) $this->projectId,
            'requestedBy' => (string) $this->requestedBy,
            'requestedTonnes' => $this->requestedTonnes,
            'requestedAmount' => $this->quote?->taxableAmount(),
            'requestedVatRate' => $this->quote?->vatRate(),
            'requestedVatAmount' => $this->quote?->vatAmount(),
            'requestedVatReason' => $this->quote?->vatReason()->value,
            'billingLegalName' => $this->billing?->legalName(),
            'billingTaxId' => $this->billing?->taxId(),
            'billingStreet' => $this->billing?->street(),
            'billingPostalCode' => $this->billing?->postalCode(),
            'billingCity' => $this->billing?->city(),
            'billingCountry' => $this->billing?->country(),
            'billingEmail' => $this->billing?->email(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseRequested(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            requestedBy: new UserId($payload['requestedBy']),
            requestedTonnes: (float) $payload['requestedTonnes'],
            quote: isset($payload['requestedAmount'])
                ? new CreditPurchaseQuote(
                    taxableAmount: (float) $payload['requestedAmount'],
                    vatRate: (float) ($payload['requestedVatRate'] ?? 0.0),
                    vatAmount: (float) ($payload['requestedVatAmount'] ?? 0.0),
                    vatReason: VatExemptionReason::tryFrom((string) ($payload['requestedVatReason'] ?? '')) ?? VatExemptionReason::Standard,
                )
                : null,
            billing: isset($payload['billingLegalName'])
                ? new CreditPurchaseBilling(
                    legalName: (string) $payload['billingLegalName'],
                    taxId: (string) ($payload['billingTaxId'] ?? ''),
                    street: (string) ($payload['billingStreet'] ?? ''),
                    postalCode: (string) ($payload['billingPostalCode'] ?? ''),
                    city: (string) ($payload['billingCity'] ?? ''),
                    country: (string) ($payload['billingCountry'] ?? ''),
                    email: (string) ($payload['billingEmail'] ?? ''),
                )
                : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
