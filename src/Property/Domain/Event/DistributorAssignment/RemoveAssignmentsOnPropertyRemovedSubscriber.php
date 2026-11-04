<?php

declare(strict_types=1);

namespace App\Property\Domain\Event\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveAssignmentsOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private DistributorAssignmentRepository $distributorAssignmentRepository,
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

        assert($event instanceof PropertyRemoved);

        $this->removeAll(new DistributorAssignmentFilter(propertyId: $event->propertyId(), limit: 0));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyRemoved;
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
