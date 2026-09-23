<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\List;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;

class ListCarbonFootprintReportsRequest extends CarbonFootprintReportFilter
{
    public function __construct(
        ?CarbonFootprintReportStatus $status = CarbonFootprintReportStatus::PendingReview,
        ?CarbonFootprintReportSubjectType $subjectType = null,
        ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        parent::__construct($status, $subjectType, $accountId, $limit);
    }
}
