<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseReceived extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseReceived';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private CreditPurchaseId $sourceCreditPurchaseId,
        private AccountId $accountId,
        private ProjectId $projectId,
        private float $purchasedTonnes,
        private float $offsetTonnes,
        private ?float $amount,
        private CreditPurchasePaymentType $paymentType,
        private string $reference,
        private int $lotNumber,
        private ?MediaId $certificate,
        private UserId $transferredBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function sourceCreditPurchaseId(): CreditPurchaseId
    {
        return $this->sourceCreditPurchaseId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function purchasedTonnes(): float
    {
        return $this->purchasedTonnes;
    }

    public function offsetTonnes(): float
    {
        return $this->offsetTonnes;
    }

    public function receivedTonnes(): float
    {
        return $this->purchasedTonnes - $this->offsetTonnes;
    }

    public function amount(): ?float
    {
        return $this->amount;
    }

    public function paymentType(): CreditPurchasePaymentType
    {
        return $this->paymentType;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function lotNumber(): int
    {
        return $this->lotNumber;
    }

    public function certificate(): ?MediaId
    {
        return $this->certificate;
    }

    public function transferredBy(): UserId
    {
        return $this->transferredBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
            'sourceCreditPurchaseId' => (string) $this->sourceCreditPurchaseId,
            'accountId' => (string) $this->accountId,
            'projectId' => (string) $this->projectId,
            'purchasedTonnes' => $this->purchasedTonnes,
            'offsetTonnes' => $this->offsetTonnes,
            'amount' => $this->amount,
            'paymentType' => $this->paymentType->value,
            'reference' => $this->reference,
            'lotNumber' => $this->lotNumber,
            'certificate' => null !== $this->certificate ? (string) $this->certificate : null,
            'transferredBy' => (string) $this->transferredBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseReceived(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            sourceCreditPurchaseId: new CreditPurchaseId($payload['sourceCreditPurchaseId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            purchasedTonnes: (float) $payload['purchasedTonnes'],
            offsetTonnes: (float) $payload['offsetTonnes'],
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            paymentType: CreditPurchasePaymentType::tryFrom((string) ($payload['paymentType'] ?? ''))
                ?? CreditPurchasePaymentType::PaidByBioscore,
            reference: $payload['reference'],
            lotNumber: (int) $payload['lotNumber'],
            certificate: null !== $payload['certificate'] ? new MediaId($payload['certificate']) : null,
            transferredBy: new UserId($payload['transferredBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
