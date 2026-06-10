<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses;

use App\FoodLoss\Application\ProtocolAccess\ProtocolAccessAuthorizer;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListProtocolAccessesService
{
    public function __construct(
        private ProtocolAccessRepository $protocolAccessRepository,
        private EmployeeRepository $employeeRepository,
        private ProtocolAccessAuthorizer $authorizer,
    ) {}

    public function __invoke(ListProtocolAccessesRequest $request, User $user): ListProtocolAccessesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            $this->dummyAccess($request->accountId),
            SecurityAction::List,
            $user,
        );

        $employees = $this->employeeRepository->findByFilter(
            new EmployeeFilter(accountId: $request->accountId, limit: 0),
        );

        $lastAccesses = $this->protocolAccessRepository->findLastAccessPerEmployee($request->protocolId);

        $lastAccessByEmployee = $this->indexByEmployee($lastAccesses);

        $summaries = array_map(
            static fn(Employee $employee) => new ProtocolAccessSummary(
                employee: $employee,
                lastAccessedAt: isset($lastAccessByEmployee[(string) $employee->id()])
                    ? $lastAccessByEmployee[(string) $employee->id()]->accessedAt()
                    : null,
            ),
            $employees,
        );

        usort(
            $summaries,
            static fn(ProtocolAccessSummary $a, ProtocolAccessSummary $b) => match (true) {
                null !== $a->lastAccessedAt && null === $b->lastAccessedAt => -1,
                null === $a->lastAccessedAt && null !== $b->lastAccessedAt => 1,
                null !== $a->lastAccessedAt => $b->lastAccessedAt <=> $a->lastAccessedAt,
                default => strcmp((string) $a->employee, (string) $b->employee),
            },
        );

        return new ListProtocolAccessesResponse($summaries);
    }

    private function dummyAccess(AccountId $accountId): ProtocolAccess
    {
        return new ProtocolAccess(
            id: ProtocolAccessId::generate(),
            accountId: $accountId,
        );
    }

    /**
     * @param array<ProtocolAccess> $accesses
     *
     * @return array<string, ProtocolAccess>
     */
    private function indexByEmployee(array $accesses): array
    {
        $indexed = [];

        foreach ($accesses as $access) {
            $indexed[(string) $access->employeeId()] = $access;
        }

        return $indexed;
    }
}
