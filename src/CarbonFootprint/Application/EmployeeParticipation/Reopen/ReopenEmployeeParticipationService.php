<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Reopen;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReopenEmployeeParticipationService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EmployeeParticipationRepository $employeeParticipationRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
    ) {}

    public function __invoke(ReopenEmployeeParticipationRequest $request, User $user): void
    {
        $employeeParticipation = $this->employeeParticipationRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        if (null === $employeeParticipation) {
            return;
        }

        $employeeParticipation->reopen();

        $this->employeeParticipationRepository->add($employeeParticipation);

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null !== $eventCarbonFootprint) {
            $eventCarbonFootprint->removeEmployeeActivities();
            $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
        }
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReopenEmployeeParticipationRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReopenEmployeeParticipationRequest;
    }
}
