<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CreditPurchaseRejected extends DomainEvent
{
    protected static string $eventName = 'creditPurchaseRejected';

    public function __construct(
        private CreditPurchaseId $creditPurchaseId,
        private string $reason,
        private UserId $rejectedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function rejectedBy(): UserId
    {
        return $this->rejectedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->creditPurchaseId();
    }

    public function payload(): array
    {
        return [
            'creditPurchaseId' => (string) $this->creditPurchaseId,
            'reason' => $this->reason,
            'rejectedBy' => (string) $this->rejectedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CreditPurchaseRejected(
            creditPurchaseId: new CreditPurchaseId($payload['creditPurchaseId']),
            reason: $payload['reason'],
            rejectedBy: new UserId($payload['rejectedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
