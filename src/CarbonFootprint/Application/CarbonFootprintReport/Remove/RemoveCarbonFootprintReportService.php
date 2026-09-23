<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Remove;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStorage;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTranslation;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private CarbonFootprintReportStorage $storage,
    ) {}

    public function __invoke(RemoveCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::Remove, $user);

        foreach ($report->translations() as $translation) {
            assert($translation instanceof CarbonFootprintReportTranslation);
            $this->storage->delete($translation->filePath());
        }

        $report->remove();

        $this->reportRepository->remove($report);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RemoveCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCarbonFootprintReportRequest;
    }
}
