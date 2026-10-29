<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register\RegisterOrganizationalEmployeeContributionRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register\RegisterOrganizationalEmployeeContributionService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesResponse;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssignOrganizationalEmployeesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private EmployeeRepository $employeeRepository,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalEmployeeParticipationRepository $participationRepository,
        private RegisterOrganizationalEmployeeContributionService $registerContribution,
        private SyncOrganizationalEmployeeActivitiesService $syncActivities,
    ) {}

    public function __invoke(AssignOrganizationalEmployeesRequest $request, User $user): SyncOrganizationalEmployeeActivitiesResponse
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::Edit, $user);

        $located->ensureIsEditable();

        $accountId = $located->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('CarbonFootprint has no accountId');
        }

        $employeeIds = $this->uniqueEmployeeIds($request->employeeIds);

        if ($request->totalEmployees < max(1, count($employeeIds))) {
            throw new \InvalidArgumentException('El número total de empleados no puede ser inferior al número de empleados seleccionados');
        }

        foreach ($employeeIds as $employeeId) {
            $employee = $this->employeeRepository->find($employeeId);

            if (null === $employee || $employee->isRemoved() || !$employee->accountId()->equals($accountId)) {
                throw new \InvalidArgumentException('Employee not found');
            }
        }

        $requestedIds = array_map(static fn(EmployeeId $employeeId): string => (string) $employeeId, $employeeIds);
        $existingContributions = $this->contributionRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId);
        $existingIds = [];

        foreach ($existingContributions as $contribution) {
            $employeeId = $contribution->employeeId();

            if (null === $employeeId) {
                continue;
            }

            $existingIds[] = (string) $employeeId;
            $isStillAssigned = in_array((string) $employeeId, $requestedIds, true);

            if (!$isStillAssigned && !$contribution->status()->isIgnored()) {
                $contribution->ignore();
                $this->contributionRepository->add($contribution);
            }

            if ($isStillAssigned && $contribution->status()->isIgnored()) {
                $contribution->unignore();
                $this->contributionRepository->add($contribution);
            }
        }

        foreach ($employeeIds as $employeeId) {
            if (in_array((string) $employeeId, $existingIds, true)) {
                continue;
            }

            ($this->registerContribution)(
                new RegisterOrganizationalEmployeeContributionRequest($request->subjectType, $request->carbonFootprintId, $employeeId),
                $user,
            );
        }

        $participation = $this->participationRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId);

        if (null === $participation) {
            $participation = new OrganizationalEmployeeParticipation(OrganizationalEmployeeParticipationId::generate());
            $participation->register($request->subjectType, $request->carbonFootprintId, $accountId);
        }

        $participation->update($request->totalEmployees, $request->confidenceLevel);
        $this->participationRepository->add($participation);

        $response = ($this->syncActivities)(
            new SyncOrganizationalEmployeeActivitiesRequest($request->subjectType, $request->carbonFootprintId),
            $user,
        );

        $located->carbonFootprint->answerEmployees();
        $this->locator->save($located);

        return $response;
    }

    /**
     * @param list<EmployeeId> $employeeIds
     *
     * @return list<EmployeeId>
     */
    private function uniqueEmployeeIds(array $employeeIds): array
    {
        $unique = [];

        foreach ($employeeIds as $employeeId) {
            $unique[(string) $employeeId] = $employeeId;
        }

        return array_values($unique);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssignOrganizationalEmployeesRequest;
    }
}
