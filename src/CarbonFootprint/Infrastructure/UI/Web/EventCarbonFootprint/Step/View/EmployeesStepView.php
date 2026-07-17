<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EmployeeContribution\List\ListEmployeeContributionsRequest;
use App\CarbonFootprint\Application\EmployeeContribution\List\ListEmployeeContributionsService;
use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\HumanResources\Application\Employee\List\ListEmployeesService;
use App\Security\Domain\Model\User\User;

final class EmployeesStepView
{
    public function __construct(
        private ListEmployeesService $listEmployees,
        private ListEmployeeContributionsService $listEmployeeContributions,
        private GetEmployeeParticipationStatsService $getEmployeeParticipationStats,
        private EmployeeParticipationRepository $employeeParticipationRepository,
    ) {}

    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, User $user): array
    {
        $accountId = $eventCarbonFootprint->accountId();

        $employeesResponse = ($this->listEmployees)(new ListEmployeesRequest(accountId: $accountId, limit: 0), $user);
        $contributionsResponse = ($this->listEmployeeContributions)(new ListEmployeeContributionsRequest($eventCarbonFootprint->id()), $user);

        $assignedEmployeeIds = [];
        foreach ($contributionsResponse->employeeContributions as $contribution) {
            if ($contribution->status()->isIgnored()) {
                continue;
            }

            $employeeId = $contribution->employeeId();

            if (null !== $employeeId) {
                $assignedEmployeeIds[(string) $employeeId] = true;
            }
        }

        $employeesById = [];
        foreach ($employeesResponse->employees as $employee) {
            $employeesById[(string) $employee->id()] = $employee;
        }

        $stats = null !== $this->employeeParticipationRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id())
            ? ($this->getEmployeeParticipationStats)(new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()), $user)
            : null;

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'employees' => $employeesResponse->employees,
            'assignedEmployeeIds' => $assignedEmployeeIds,
            'contributions' => $contributionsResponse->employeeContributions,
            'employeesById' => $employeesById,
            'employeeParticipation' => $stats?->employeeParticipation,
            'pendingCount' => null !== $stats ? $stats->pendingCount : 0,
            'answeredCount' => null !== $stats ? $stats->answeredCount : 0,
            'ignoredCount' => null !== $stats ? $stats->ignoredCount : 0,
            'answeredPercentage' => $stats?->answeredPercentage,
            'confidenceLevels' => ConfidenceLevel::cases(),
        ];
    }
}
