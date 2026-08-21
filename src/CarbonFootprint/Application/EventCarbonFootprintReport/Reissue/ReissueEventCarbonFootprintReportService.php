<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReissueEventCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintReportRepository $reportRepository,
        private EventCarbonFootprintReportAuthorizer $authorizer,
    ) {}

    public function __invoke(ReissueEventCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('EventCarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

        if ($report->status()->isPending()) {
            return;
        }

        $report->reissue();

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateEventCarbonFootprintReportRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReissueEventCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReissueEventCarbonFootprintReportRequest;
    }
}
