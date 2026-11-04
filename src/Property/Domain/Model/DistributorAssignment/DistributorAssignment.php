<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class DistributorAssignment
{
    use AggregateRoot;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected DistributorAssignmentId $id,
        protected ?AccountId $distributorAccountId = null,
        protected ?AccountId $accountId = null,
        protected ?PropertyId $propertyId = null,
        protected ?UserId $registeredBy = null,
        protected bool $removed = false,
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): DistributorAssignmentId
    {
        return $this->id;
    }

    public function distributorAccountId(): ?AccountId
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

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy;
    }

    public function isForProperty(): bool
    {
        return null !== $this->propertyId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function registerForAccount(AccountId $distributorAccountId, AccountId $accountId, UserId $registeredBy): void
    {
        if ($distributorAccountId->equals($accountId)) {
            throw new DistributorCannotDistributeItselfException($distributorAccountId);
        }

        $this->recordApplyAndPublish(
            new DistributorAssignmentRegistered(
                distributorAssignmentId: $this->id(),
                distributorAccountId: $distributorAccountId,
                accountId: $accountId,
                propertyId: null,
                registeredBy: $registeredBy,
            ),
        );
    }

    public function registerForProperty(AccountId $distributorAccountId, PropertyId $propertyId, UserId $registeredBy): void
    {
        $this->recordApplyAndPublish(
            new DistributorAssignmentRegistered(
                distributorAssignmentId: $this->id(),
                distributorAccountId: $distributorAccountId,
                accountId: null,
                propertyId: $propertyId,
                registeredBy: $registeredBy,
            ),
        );
    }

    public function remove(UserId $removedBy, bool $notifyDistributor): void
    {
        if (!$this->distributorAccountId instanceof AccountId) {
            return;
        }

        $this->recordApplyAndPublish(
            new DistributorAssignmentRemoved(
                distributorAssignmentId: $this->id(),
                distributorAccountId: $this->distributorAccountId,
                accountId: $this->accountId,
                propertyId: $this->propertyId,
                removedBy: $removedBy,
                notifyDistributor: $notifyDistributor,
            ),
        );
    }

    public function applyDistributorAssignmentRegistered(DistributorAssignmentRegistered $event): void
    {
        $this->distributorAccountId = $event->distributorAccountId();
        $this->accountId = $event->accountId();
        $this->propertyId = $event->propertyId();
        $this->registeredBy = $event->registeredBy();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyDistributorAssignmentRemoved(DistributorAssignmentRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }
}
