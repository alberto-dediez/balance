<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\LinkUser;

use App\HumanResources\Domain\Model\Employee\EmployeeRepository;

class LinkUserService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
    ) {}

    public function __invoke(LinkUserRequest $request): LinkUserResponse
    {
        $employee = $this->employeeRepository->find($request->employeeId);

        if (null === $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $employee->linkUser($request->userId);

        $this->employeeRepository->add($employee);

        return new LinkUserResponse(
            employeeId: $employee->id(),
            userId: $request->userId,
        );
    }
}
