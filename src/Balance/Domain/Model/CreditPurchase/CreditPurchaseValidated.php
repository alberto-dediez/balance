<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseValidated extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseValidated';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private float $requestedTonnes,
        private float $purchasedTonnes,
        private CreditPurchasePaymentType $paymentType,
        private string $reference,
        private ?MediaId $certificate,
        private UserId $validatedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function requestedTonnes(): float
    {
        return $this->requestedTonnes;
    }

    public function purchasedTonnes(): float
    {
        return $this->purchasedTonnes;
    }

    public function paymentType(): CreditPurchasePaymentType
    {
        return $this->paymentType;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function certificate(): ?MediaId
    {
        return $this->certificate;
    }

    public function validatedBy(): UserId
    {
        return $this->validatedBy;
    }

    public function isPartiallyFulfilled(): bool
    {
        return $this->purchasedTonnes < $this->requestedTonnes;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
            'requestedTonnes' => $this->requestedTonnes,
            'purchasedTonnes' => $this->purchasedTonnes,
            'paymentType' => $this->paymentType->value,
            'reference' => $this->reference,
            'certificate' => null !== $this->certificate ? (string) $this->certificate : null,
            'validatedBy' => (string) $this->validatedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseValidated(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            requestedTonnes: (float) $payload['requestedTonnes'],
            purchasedTonnes: (float) $payload['purchasedTonnes'],
            paymentType: CreditPurchasePaymentType::tryFrom((string) ($payload['paymentType'] ?? ''))
                ?? CreditPurchasePaymentType::PaidByClient,
            reference: $payload['reference'],
            certificate: null !== $payload['certificate'] ? new MediaId($payload['certificate']) : null,
            validatedBy: new UserId($payload['validatedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
