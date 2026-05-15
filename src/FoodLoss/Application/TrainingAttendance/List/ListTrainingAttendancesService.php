<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance\List;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListTrainingAttendancesService
{
    public function __construct(
        private TrainingAttendanceRepository $trainingAttendanceRepository,
        private EmployeeRepository $employeeRepository,
        private TrainingAttendanceAuthorizer $authorizer,
    ) {}

    public function __invoke(ListTrainingAttendancesRequest $request, User $user): ListTrainingAttendancesResponse
    {
        assert($request->accountId instanceof AccountId);

        $this->authorizer->denyUnlessCanDoThat(
            new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $employees = $this->employeeRepository->findByFilter(
            new EmployeeFilter(accountId: $request->accountId, limit: 0),
        );

        $attendances = $this->trainingAttendanceRepository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $request->trainingSessionId, limit: 0),
        );

        $attendancesByEmployee = $this->indexByEmployee($attendances);

        $summaries = array_map(
            static fn(Employee $employee) => new TrainingAttendanceSummary(
                employee: $employee,
                attendances: $attendancesByEmployee[(string) $employee->id()] ?? [],
            ),
            $employees,
        );

        usort(
            $summaries,
            static fn(TrainingAttendanceSummary $a, TrainingAttendanceSummary $b) => match (true) {
                $a->hasAttended() && !$b->hasAttended() => -1,
                !$a->hasAttended() && $b->hasAttended() => 1,
                default => strcmp((string) $a->employee, (string) $b->employee),
            },
        );

        return new ListTrainingAttendancesResponse($summaries);
    }

    /**
     * @param array<TrainingAttendance> $attendances
     *
     * @return array<string, array<TrainingAttendance>>
     */
    private function indexByEmployee(array $attendances): array
    {
        $indexed = [];

        foreach ($attendances as $attendance) {
            $key = (string) $attendance->employeeId();
            $indexed[$key][] = $attendance;
        }

        return $indexed;
    }
}
