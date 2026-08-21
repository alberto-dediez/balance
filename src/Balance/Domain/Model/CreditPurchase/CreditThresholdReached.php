<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditThresholdReached extends DomainEvent
{
    protected static string $eventName = 'creditThresholdReached';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private AccountId $accountId,
        private ProjectId $projectId,
        private CreditAlertThreshold $threshold,
        private float $purchasedTonnes,
        private float $consumedTonnes,
        private float $availableTonnes,
        private float $projectAvailableTonnes,
        private ?string $purchaseReference = null,
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

    public function threshold(): CreditAlertThreshold
    {
        return $this->threshold;
    }

    public function purchasedTonnes(): float
    {
        return $this->purchasedTonnes;
    }

    public function consumedTonnes(): float
    {
        return $this->consumedTonnes;
    }

    public function availableTonnes(): float
    {
        return $this->availableTonnes;
    }

    public function projectAvailableTonnes(): float
    {
        return $this->projectAvailableTonnes;
    }

    public function purchaseReference(): ?string
    {
        return $this->purchaseReference;
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
            'threshold' => $this->threshold->value,
            'purchasedTonnes' => $this->purchasedTonnes,
            'consumedTonnes' => $this->consumedTonnes,
            'availableTonnes' => $this->availableTonnes,
            'projectAvailableTonnes' => $this->projectAvailableTonnes,
            'purchaseReference' => $this->purchaseReference,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditThresholdReached(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            accountId: new AccountId($payload['accountId']),
            projectId: new ProjectId($payload['projectId']),
            threshold: CreditAlertThreshold::from((int) $payload['threshold']),
            purchasedTonnes: (float) $payload['purchasedTonnes'],
            consumedTonnes: (float) $payload['consumedTonnes'],
            availableTonnes: (float) $payload['availableTonnes'],
            projectAvailableTonnes: (float) $payload['projectAvailableTonnes'],
            purchaseReference: null !== ($payload['purchaseReference'] ?? null) ? (string) $payload['purchaseReference'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
