<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Download;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStorage;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class DownloadCarbonFootprintReportService
{
    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private CarbonFootprintReportStorage $storage,
    ) {}

    public function __invoke(DownloadCarbonFootprintReportRequest $request, User $user): DownloadCarbonFootprintReportResponse
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::View, $user);

        if (!$report->status()->isGenerated()) {
            throw new \InvalidArgumentException('CarbonFootprintReport is not generated');
        }

        $translation = $report->currentTranslation();

        if (null === $translation) {
            throw new \InvalidArgumentException('CarbonFootprintReport has no file');
        }

        return new DownloadCarbonFootprintReportResponse(
            stream: $this->storage->read($translation->filePath()),
            filename: sprintf('informe-huella-carbono-%s-%s.pdf', $report->carbonFootprintId(), $translation->locale()->value),
        );
    }
}
