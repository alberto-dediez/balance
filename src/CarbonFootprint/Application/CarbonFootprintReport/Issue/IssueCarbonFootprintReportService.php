<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Issue;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\ReportTextsTrait;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class IssueCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use ReportTextsTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private ReportPermissionsPolicy $reportPermissionsPolicy,
    ) {}

    public function __invoke(IssueCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

        $permissions = $this->reportPermissionsPolicy->permissionsFor($report->accountId(), $user);

        if (!$permissions->canEditTexts() || !$permissions->autoReview()) {
            throw new UnauthorizedActionException($report, SecurityAction::Edit);
        }

        $report->approve($user->id(), true, $this->textsFrom($report, $request->sections));

        $this->reportRepository->add($report);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new GenerateCarbonFootprintReportPdfRequest($report->id()),
                priority: Priority::LOW,
            ),
            $user,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof IssueCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof IssueCarbonFootprintReportRequest;
    }
}
