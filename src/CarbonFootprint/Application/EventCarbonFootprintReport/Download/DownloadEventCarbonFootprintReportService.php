<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Download;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStorage;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class DownloadEventCarbonFootprintReportService
{
    public function __construct(
        private EventCarbonFootprintReportRepository $reportRepository,
        private EventCarbonFootprintReportAuthorizer $authorizer,
        private EventCarbonFootprintReportStorage $storage,
    ) {}

    public function __invoke(DownloadEventCarbonFootprintReportRequest $request, User $user): DownloadEventCarbonFootprintReportResponse
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('EventCarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::View, $user);

        if (!$report->status()->isGenerated()) {
            throw new \InvalidArgumentException('EventCarbonFootprintReport is not generated');
        }

        $translation = $report->currentTranslation();

        if (null === $translation) {
            throw new \InvalidArgumentException('EventCarbonFootprintReport has no file');
        }

        return new DownloadEventCarbonFootprintReportResponse(
            stream: $this->storage->read($translation->filePath()),
            filename: sprintf('informe-huella-carbono-%s-%s.pdf', (string) $report->eventCarbonFootprintId(), $translation->locale()->value),
        );
    }
}
