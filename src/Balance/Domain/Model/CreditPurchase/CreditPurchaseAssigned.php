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

final class CreditPurchaseAssigned extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseAssigned';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private AccountId $accountId,
        private ProjectId $projectId,
        private float $purchasedTonnes,
        private ?float $amount,
        private CreditPurchasePaymentType $paymentType,
        private string $reference,
        private ?MediaId $certificate,
        private UserId $assignedBy,
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

    public function purchasedTonnes(): float
    {
        return $this->purchasedTonnes;
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

    public function certificate(): ?MediaId
    {
        return $this->certificate;
    }

    public function assignedBy(): UserId
    {
        return $this->assignedBy;
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
            'purchasedTonnes' => $this->purchasedTonnes,
            'amount' => $this->amount,
            'paymentType' => $this->paymentType->value,
            'reference' => $this->reference,
            'certificate' => null !== $this->certificate ? (string) $this->certificate : null,
            'assignedBy' => (string) $this->assignedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseAssigned(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            purchasedTonnes: (float) $payload['purchasedTonnes'],
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            paymentType: CreditPurchasePaymentType::tryFrom((string) ($payload['paymentType'] ?? ''))
                ?? CreditPurchasePaymentType::PaidByBioscore,
            reference: $payload['reference'],
            certificate: isset($payload['certificate']) ? new MediaId($payload['certificate']) : null,
            assignedBy: new UserId($payload['assignedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
