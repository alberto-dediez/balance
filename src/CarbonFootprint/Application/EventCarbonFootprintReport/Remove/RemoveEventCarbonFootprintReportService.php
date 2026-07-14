<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStorage;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTranslation;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveEventCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintReportRepository $reportRepository,
        private EventCarbonFootprintReportAuthorizer $authorizer,
        private EventCarbonFootprintReportStorage $storage,
    ) {}

    public function __invoke(RemoveEventCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('EventCarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Remove, $user);

        foreach ($report->translations() as $translation) {
            assert($translation instanceof EventCarbonFootprintReportTranslation);
            $this->storage->delete($translation->filePath());
        }

        $report->remove();

        $this->reportRepository->remove($report);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RemoveEventCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveEventCarbonFootprintReportRequest;
    }
}
