<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\List;

use App\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizer;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class ListDistributorAssignmentsService
{
    public function __construct(
        private readonly DistributorAssignmentRepository $repository,
        private readonly PropertyRepository $propertyRepository,
        private readonly DistributorAssignmentAuthorizer $authorizer,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function __invoke(ListDistributorAssignmentsRequest $request, User $user): ListDistributorAssignmentsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DistributorAssignment(id: DistributorAssignmentId::generate()), Action::List, $user);

        $request = clone $request;

        if (!$this->authorizer->isAdmin($user)) {
            $currentAccountId = $this->currentAccountIdProvider->currentAccountId();

            $request->accountId = $currentAccountId;
            $request->propertyIds = $currentAccountId instanceof AccountId ? $this->propertyIdsOf($currentAccountId) : [];
            $request->distributorAccountId = null;
            $request->propertyId = null;
        } elseif ($request->accountId instanceof AccountId) {
            $request->propertyIds = $this->propertyIdsOf($request->accountId);
        }

        return new ListDistributorAssignmentsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }

    /** @return list<PropertyId> */
    private function propertyIdsOf(AccountId $accountId): array
    {
        return array_map(
            static fn(Property $property): PropertyId => $property->id(),
            array_values($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0))),
        );
    }
}
