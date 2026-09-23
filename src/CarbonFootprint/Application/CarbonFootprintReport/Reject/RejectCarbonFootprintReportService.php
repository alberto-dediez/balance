<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Reject;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RejectCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
    ) {}

    public function __invoke(RejectCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Admin, $user);

        $report->reject($user->id(), $request->comment);

        $this->reportRepository->add($report);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RejectCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RejectCarbonFootprintReportRequest;
    }
}
