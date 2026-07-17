<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Application\EmployeeContribution\Register\RegisterEmployeeContributionRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Register\RegisterEmployeeContributionService;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignEmployeesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private EmployeeContributionRepository $employeeContributionRepository,
        private RegisterEmployeeContributionService $registerEmployeeContributionService,
        private RegisterEmployeeParticipationService $registerEmployeeParticipationService,
        private EmployeeParticipationRepository $employeeParticipationRepository,
    ) {}

    public function __invoke(AssignEmployeesRequest $request, User $user): AssignEmployeesResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        if (null !== $request->totalEmployees && $request->totalEmployees < 1) {
            throw new \InvalidArgumentException('El número total de empleados debe ser al menos 1');
        }

        if (null !== $request->totalEmployees && $request->totalEmployees < count($request->employees)) {
            throw new \InvalidArgumentException('El número total de empleados no puede ser inferior al número de empleados seleccionados');
        }

        $existingContributions = $this->employeeContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        $requestedEmployeeIds = array_map(
            static fn(AssignedEmployee $assignedEmployee) => (string) $assignedEmployee->employeeId,
            $request->employees,
        );

        foreach ($existingContributions as $existingContribution) {
            $subjectEmployeeId = $existingContribution->employeeId();

            if (null === $subjectEmployeeId) {
                continue;
            }

            $isStillAssigned = in_array((string) $subjectEmployeeId, $requestedEmployeeIds, true);

            if (!$isStillAssigned && !$existingContribution->status()->isIgnored()) {
                $existingContribution->ignore();
                $this->employeeContributionRepository->add($existingContribution);
            }

            if ($isStillAssigned && $existingContribution->status()->isIgnored()) {
                $existingContribution->unignore();
                $this->employeeContributionRepository->add($existingContribution);
            }
        }

        foreach ($request->employees as $assignedEmployee) {
            $alreadyExists = false;

            foreach ($existingContributions as $existingContribution) {
                $subjectEmployeeId = $existingContribution->employeeId();

                if ($subjectEmployeeId instanceof EmployeeId && $subjectEmployeeId->equals($assignedEmployee->employeeId)) {
                    $alreadyExists = true;

                    break;
                }
            }

            if ($alreadyExists) {
                continue;
            }

            ($this->registerEmployeeContributionService)(
                new RegisterEmployeeContributionRequest(
                    eventCarbonFootprintId: $request->eventCarbonFootprintId,
                    employeeId: $assignedEmployee->employeeId,
                ),
                $user,
            );
        }

        $participationResponse = ($this->registerEmployeeParticipationService)(
            new RegisterEmployeeParticipationRequest($request->eventCarbonFootprintId),
            $user,
        );

        $employeeParticipation = $participationResponse->employeeParticipation;
        $employeeParticipation->update($request->totalEmployees, $request->confidenceLevel);
        $this->employeeParticipationRepository->add($employeeParticipation);

        $eventCarbonFootprint->answerEmployees();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return new AssignEmployeesResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignEmployeesRequest;
    }
}
