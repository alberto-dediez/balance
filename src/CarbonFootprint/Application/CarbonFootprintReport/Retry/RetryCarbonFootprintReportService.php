<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Retry;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RetryCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
    ) {}

    public function __invoke(RetryCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

        $report->retry();

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: $report->status()->isGeneratingPdf()
                    ? new GenerateCarbonFootprintReportPdfRequest($report->id())
                    : new GenerateCarbonFootprintReportTextsRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RetryCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RetryCarbonFootprintReportRequest;
    }
}
