<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GetForReview;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetCarbonFootprintReportForReviewService
{
    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportAuthorizer $authorizer,
        private CarbonFootprintReportDataCollectorRegistry $collectors,
    ) {}

    public function __invoke(GetCarbonFootprintReportForReviewRequest $request, User $user): GetCarbonFootprintReportForReviewResponse
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \InvalidArgumentException('CarbonFootprintReport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($report, SecurityAction::View, $user);

        $data = $this->collectors->collect($report);

        return new GetCarbonFootprintReportForReviewResponse(
            report: $report,
            subjectName: $data->subjectName(),
            calculationYear: $data->calculationYear(),
            totalEmissions: $data->totalEmissions(),
        );
    }
}
