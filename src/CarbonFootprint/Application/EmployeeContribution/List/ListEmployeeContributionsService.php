<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\List;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ListEmployeeContributionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private EmployeeContributionRepository $employeeContributionRepository,
    ) {}

    public function __invoke(ListEmployeeContributionsRequest $request, User $user): ListEmployeeContributionsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        return new ListEmployeeContributionsResponse(
            $this->employeeContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ListEmployeeContributionsRequest;
    }
}
