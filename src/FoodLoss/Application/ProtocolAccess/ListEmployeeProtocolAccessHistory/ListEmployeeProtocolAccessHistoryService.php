<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory;

use App\FoodLoss\Application\ProtocolAccess\ProtocolAccessAuthorizer;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmployeeProtocolAccessHistoryService
{
    public function __construct(
        private ProtocolAccessRepository $protocolAccessRepository,
        private EmployeeRepository $employeeRepository,
        private ProtocolAccessAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmployeeProtocolAccessHistoryRequest $request, User $user): ListEmployeeProtocolAccessHistoryResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ProtocolAccess(id: ProtocolAccessId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $employee = $this->employeeRepository->find($request->employeeId);

        if (null === $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $accesses = $this->protocolAccessRepository->findHistoryByProtocolAndEmployee(
            $request->protocolId,
            $request->employeeId,
        );

        return new ListEmployeeProtocolAccessHistoryResponse(
            employee: $employee,
            accesses: $accesses,
        );
    }
}
