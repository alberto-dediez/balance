<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
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
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
