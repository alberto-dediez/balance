<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class MitigationActionFilter
{
    use Filter;

    public function __construct(
        public ?CarbonFootprintReportSubjectType $subjectType = null,
        public ?string $carbonFootprintId = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
