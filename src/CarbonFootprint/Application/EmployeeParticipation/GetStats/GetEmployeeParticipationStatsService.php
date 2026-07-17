<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\GetStats;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetEmployeeParticipationStatsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private EmployeeParticipationRepository $employeeParticipationRepository,
        private EmployeeContributionRepository $employeeContributionRepository,
    ) {}

    public function __invoke(GetEmployeeParticipationStatsRequest $request, User $user): GetEmployeeParticipationStatsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $employeeParticipation = $this->employeeParticipationRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        if (null === $employeeParticipation) {
            throw new \InvalidArgumentException('EmployeeParticipation not found');
        }

        $pendingCount = 0;
        $answeredCount = 0;
        $ignoredCount = 0;

        foreach ($this->employeeContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId) as $employeeContribution) {
            match (true) {
                $employeeContribution->status()->isAnswered() => ++$answeredCount,
                $employeeContribution->status()->isIgnored() => ++$ignoredCount,
                default => ++$pendingCount,
            };
        }

        $totalEmployees = $employeeParticipation->totalEmployees();

        $answeredPercentage = (null !== $totalEmployees && $totalEmployees > 0)
            ? min(100.0, ($answeredCount / $totalEmployees) * 100.0)
            : null;

        return new GetEmployeeParticipationStatsResponse(
            employeeParticipation: $employeeParticipation,
            pendingCount: $pendingCount,
            answeredCount: $answeredCount,
            ignoredCount: $ignoredCount,
            answeredPercentage: $answeredPercentage,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetEmployeeParticipationStatsRequest;
    }
}
