<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Reissue;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ReissueCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
    ) {}

    public function __invoke(ReissueCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

        if (!$report->status()->isGenerated()) {
            throw new CarbonFootprintReportTransitionNotAllowedException('Only a generated report can be reissued');
        }

        $report->reissue();

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateCarbonFootprintReportTextsRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof ReissueCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ReissueCarbonFootprintReportRequest;
    }
}
