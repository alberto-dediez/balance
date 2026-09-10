<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseTransferred extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseTransferred';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private CreditPurchaseId $destinationCreditPurchaseId,
        private AccountId $destinationAccountId,
        private float $transferredTonnes,
        private float $transferredAmount,
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

    public function destinationCreditPurchaseId(): CreditPurchaseId
    {
        return $this->destinationCreditPurchaseId;
    }

    public function destinationAccountId(): AccountId
    {
        return $this->destinationAccountId;
    }

    public function transferredTonnes(): float
    {
        return $this->transferredTonnes;
    }

    public function transferredAmount(): float
    {
        return $this->transferredAmount;
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
            'destinationCreditPurchaseId' => (string) $this->destinationCreditPurchaseId,
            'destinationAccountId' => (string) $this->destinationAccountId,
            'transferredTonnes' => $this->transferredTonnes,
            'transferredAmount' => $this->transferredAmount,
            'transferredBy' => (string) $this->transferredBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseTransferred(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            destinationCreditPurchaseId: new CreditPurchaseId($payload['destinationCreditPurchaseId']),
            destinationAccountId: new AccountId($payload['destinationAccountId']),
            transferredTonnes: (float) $payload['transferredTonnes'],
            transferredAmount: (float) $payload['transferredAmount'],
            transferredBy: new UserId($payload['transferredBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
