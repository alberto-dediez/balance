<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\List;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListEmployeesService
{
    public function __construct(
        private EmployeeRepository $repository,
        private EmployeeAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmployeesRequest $request, User $user): ListEmployeesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Employee(id: EmployeeId::generate(), accountId: $request->accountId ?? AccountId::generate()), SecurityAction::List, $user);

        $employees = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListEmployeesResponse($employees, $totalResults);
    }
}
