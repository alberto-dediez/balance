<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Register;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeWithoutAccountException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEmployeeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EmployeeAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterEmployeeRequest $request, User $user): RegisterEmployeeResponse
    {
        $id = EmployeeId::generate();

        if (!$request->accountId instanceof AccountId) {
            throw new EmployeeWithoutAccountException();
        }

        $this->authorizer->denyUnlessCanDoThat(new Employee(id: $id, accountId: $request->accountId), SecurityAction::List, $user);

        $employee = new Employee(id: $id);
        $employee->register(
            name: $request->name,
            email: $request->email,
            phone: $request->phone,
            address: $request->address(),
            accountId: $request->accountId ?? throw new \InvalidArgumentException('accountId is required'),
            userId: $request->userId,
        );

        $this->employeeRepository->add($employee);

        return new RegisterEmployeeResponse(
            employeeId: $employee->id(),
            name: $employee->name(),
            email: $employee->email(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEmployeeRequest;
    }
}
