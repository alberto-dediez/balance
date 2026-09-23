<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\List;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCarbonFootprintReportsService
{
    public function __construct(
        private CarbonFootprintReportRepository $repository,
        private CarbonFootprintReportAuthorizer $authorizer,
    ) {}

    public function __invoke(ListCarbonFootprintReportsRequest $request, User $user): ListCarbonFootprintReportsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CarbonFootprintReport(id: CarbonFootprintReportId::generate()), SecurityAction::Admin, $user);

        return new ListCarbonFootprintReportsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
