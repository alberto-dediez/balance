<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Update;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEmployeeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EmployeeAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmployeeRequest $request, User $user): UpdateEmployeeResponse
    {
        $employee = $this->employeeRepository->find($request->employeeId);

        if (null == $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $this->authorizer->denyUnlessCanDoThat($employee, SecurityAction::Edit, $user);

        $employee->update(
            name: $request->name,
            email: $request->email,
            phone: $request->phone,
            address: $request->address(),
            accountId: $request->accountId,
            userId: $request->userId,
        );

        $this->employeeRepository->add($employee);

        return new UpdateEmployeeResponse(
            employeeId: $employee->id(),
            name: $employee->name(),
            email: $employee->email(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmployeeRequest;
    }
}
