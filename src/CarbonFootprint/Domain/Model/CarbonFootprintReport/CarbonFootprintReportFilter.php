<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class CarbonFootprintReportFilter
{
    use Filter;

    public function __construct(
        public ?CarbonFootprintReportStatus $status = null,
        public ?CarbonFootprintReportSubjectType $subjectType = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
