<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\UpdateTexts;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\ReportPermissionsTrait;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTransitionNotAllowedException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateCarbonFootprintReportTextsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use ReportPermissionsTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private AccountRepository $accountRepository,
    ) {}

    public function __invoke(UpdateCarbonFootprintReportTextsRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        if ($report->status()->isDraft()) {
            $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Edit, $user);

            if (!$this->reportPermissions($report)->canEditTexts()) {
                throw new UnauthorizedActionException($report, SecurityAction::Edit);
            }
        } elseif ($report->status()->isPendingReview()) {
            $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Admin, $user);
        } else {
            throw new CarbonFootprintReportTransitionNotAllowedException('The report texts can only be changed while in draft or pending review');
        }

        $report->changeTexts($this->textsFrom($report, $request->sections));

        $this->reportRepository->add($report);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof UpdateCarbonFootprintReportTextsRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateCarbonFootprintReportTextsRequest;
    }
}
