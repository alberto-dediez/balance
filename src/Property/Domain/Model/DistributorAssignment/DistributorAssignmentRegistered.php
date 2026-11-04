<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DistributorAssignmentRegistered extends DomainEvent
{
    protected static string $eventName = 'distributorAssignmentRegistered';

    public function __construct(
        private readonly DistributorAssignmentId $distributorAssignmentId,
        private readonly AccountId $distributorAccountId,
        private readonly ?AccountId $accountId,
        private readonly ?PropertyId $propertyId,
        private readonly UserId $registeredBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function distributorAssignmentId(): DistributorAssignmentId
    {
        return $this->distributorAssignmentId;
    }

    public function distributorAccountId(): AccountId
    {
        return $this->distributorAccountId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->distributorAssignmentId;
    }

    public function payload(): array
    {
        return [
            'distributorAssignmentId' => (string) $this->distributorAssignmentId,
            'distributorAccountId' => (string) $this->distributorAccountId,
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'propertyId' => $this->propertyId instanceof PropertyId ? (string) $this->propertyId : null,
            'registeredBy' => (string) $this->registeredBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DistributorAssignmentRegistered(
            distributorAssignmentId: new DistributorAssignmentId($payload['distributorAssignmentId']),
            distributorAccountId: new AccountId($payload['distributorAccountId']),
            accountId: null !== ($payload['accountId'] ?? null) ? new AccountId($payload['accountId']) : null,
            propertyId: null !== ($payload['propertyId'] ?? null) ? new PropertyId($payload['propertyId']) : null,
            registeredBy: new UserId($payload['registeredBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
