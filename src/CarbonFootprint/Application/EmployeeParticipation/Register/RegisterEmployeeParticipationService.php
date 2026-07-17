<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Register;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEmployeeParticipationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmployeeParticipationRepository $employeeParticipationRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
    ) {}

    public function __invoke(RegisterEmployeeParticipationRequest $request, User $user): RegisterEmployeeParticipationResponse
    {
        $existing = $this->employeeParticipationRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        if (null !== $existing) {
            return new RegisterEmployeeParticipationResponse($existing);
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $accountId = $eventCarbonFootprint->accountId();

        if (null == $accountId) {
            throw new \InvalidArgumentException('EventCarbonFootprint has no accountId');
        }

        $employeeParticipation = new EmployeeParticipation(EmployeeParticipationId::generate());
        $employeeParticipation->register(
            eventCarbonFootprintId: $request->eventCarbonFootprintId,
            accountId: $accountId,
        );

        $this->employeeParticipationRepository->add($employeeParticipation);

        return new RegisterEmployeeParticipationResponse($employeeParticipation);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEmployeeParticipationRequest;
    }
}
