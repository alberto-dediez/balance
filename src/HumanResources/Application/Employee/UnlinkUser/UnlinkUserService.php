<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\UnlinkUser;

use App\HumanResources\Domain\Model\Employee\EmployeeRepository;

class UnlinkUserService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
    ) {}

    public function __invoke(UnlinkUserRequest $request): UnlinkUserResponse
    {
        $employee = $this->employeeRepository->find($request->employeeId);

        if (null === $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $employee->unlinkUser();

        $this->employeeRepository->add($employee);

        return new UnlinkUserResponse(
            employeeId: $employee->id(),
        );
    }
}
