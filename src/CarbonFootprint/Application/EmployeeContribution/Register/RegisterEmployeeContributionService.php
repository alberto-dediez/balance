<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEmployeeContributionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private EmployeeRepository $employeeRepository,
        private EmployeeContributionRepository $employeeContributionRepository,
    ) {}

    public function __invoke(RegisterEmployeeContributionRequest $request, User $user): RegisterEmployeeContributionResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $employee = $this->employeeRepository->find($request->employeeId);

        if (null == $employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $accountId = $eventCarbonFootprint->accountId();

        if (null == $accountId) {
            throw new \InvalidArgumentException('EventCarbonFootprint has no accountId');
        }

        $employeeContribution = new EmployeeContribution(EmployeeContributionId::generate());
        $employeeContribution->register(
            eventCarbonFootprintId: $request->eventCarbonFootprintId,
            accountId: $accountId,
            employeeId: $request->employeeId,
            token: EmployeeContribution::generateToken(),
            employeeName: $employee->name(),
        );

        $this->employeeContributionRepository->add($employeeContribution);

        return new RegisterEmployeeContributionResponse($employeeContribution->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEmployeeContributionRequest;
    }
}
