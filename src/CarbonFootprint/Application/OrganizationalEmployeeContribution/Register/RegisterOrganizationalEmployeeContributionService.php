<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterOrganizationalEmployeeContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private EmployeeRepository $employeeRepository,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
    ) {}

    public function __invoke(RegisterOrganizationalEmployeeContributionRequest $request, User $user): RegisterOrganizationalEmployeeContributionResponse
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $accountId = $located->accountId();

        if (null === $accountId) {
            throw new \InvalidArgumentException('CarbonFootprint has no accountId');
        }

        $employee = $this->employeeRepository->find($request->employeeId);

        if (null === $employee || $employee->isRemoved() || !$employee->accountId()->equals($accountId)) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $contribution = new OrganizationalEmployeeContribution(OrganizationalEmployeeContributionId::generate());
        $contribution->register(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            employeeId: $request->employeeId,
            token: OrganizationalEmployeeContribution::generateToken(),
            employeeName: $employee->name(),
        );

        $this->contributionRepository->add($contribution);

        return new RegisterOrganizationalEmployeeContributionResponse($contribution);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterOrganizationalEmployeeContributionRequest;
    }
}
