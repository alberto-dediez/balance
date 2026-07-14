<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterEventCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private readonly EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private readonly EventCarbonFootprintReportRepository $reportRepository,
    ) {}

    public function __invoke(RegisterEventCarbonFootprintReportRequest $request, User $user): RegisterEventCarbonFootprintReportResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        if (!$eventCarbonFootprint->status()->isCompleted()) {
            throw new \RuntimeException('EventCarbonFootprint is not completed');
        }

        if (null !== $this->reportRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId)) {
            throw new EventCarbonFootprintReportAlreadyExistsException('A report already exists for this EventCarbonFootprint');
        }

        $accountId = $eventCarbonFootprint->accountId();
        if (null === $accountId) {
            throw new \RuntimeException('EventCarbonFootprint has no account');
        }

        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register($request->eventCarbonFootprintId, $accountId, $user->id());

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateEventCarbonFootprintReportRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );

        return new RegisterEventCarbonFootprintReportResponse($report->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEventCarbonFootprintReportRequest;
    }
}
