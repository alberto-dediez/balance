<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\SubmitForReview;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\ReportTextsTrait;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SubmitCarbonFootprintReportForReviewService implements CommandHandler
{
    use RegisterHandlerTrait;
    use ReportTextsTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private ReportPermissionsPolicy $reportPermissionsPolicy,
    ) {}

    public function __invoke(SubmitCarbonFootprintReportForReviewRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

        if (!$this->reportPermissionsPolicy->permissionsFor($report->accountId(), $user)->canEditTexts()) {
            throw new UnauthorizedActionException($report, SecurityAction::Edit);
        }

        $report->submitForReview($this->textsFrom($report, $request->sections));

        $this->reportRepository->add($report);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof SubmitCarbonFootprintReportForReviewRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SubmitCarbonFootprintReportForReviewRequest;
    }
}
