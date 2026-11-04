<?php

declare(strict_types=1);

namespace App\Property\Domain\Event\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveAssignmentsOnAccountRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private DistributorAssignmentRepository $distributorAssignmentRepository,
        private PropertyRepository $propertyRepository,
        private RemoveDistributorAssignmentService $removeDistributorAssignment,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof AccountRemoved);

        $this->removeAll(new DistributorAssignmentFilter(distributorAccountId: $event->accountId(), limit: 0));
        $this->removeAll(new DistributorAssignmentFilter(
            accountId: $event->accountId(),
            propertyIds: $this->propertyIdsOf($event->accountId()),
            limit: 0,
        ));
    }

    /** @return list<PropertyId> */
    private function propertyIdsOf(AccountId $accountId): array
    {
        return array_map(
            static fn(Property $property): PropertyId => $property->id(),
            array_values($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0))),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof AccountRemoved;
    }

    private function removeAll(DistributorAssignmentFilter $filter): void
    {
        foreach ($this->distributorAssignmentRepository->findByFilter($filter) as $assignment) {
            ($this->removeDistributorAssignment)(
                new RemoveDistributorAssignmentRequest($assignment->id(), notifyDistributor: false),
                $this->loggedUser->__invoke(),
            );
        }
    }
}
