<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Remove;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveEmployeeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeRepository $repository,
        private EmployeeAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveEmployeeRequest $request, User $user): RemoveEmployeeResponse
    {
        $employee = $this->repository->find($request->employeeId);

        if (null == $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $this->authorizer->denyUnlessCanDoThat($employee, SecurityAction::Remove, $user);

        $employee->remove();

        $this->repository->add($employee);

        return new RemoveEmployeeResponse(
            employeeId: $employee->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveEmployeeRequest;
    }
}
