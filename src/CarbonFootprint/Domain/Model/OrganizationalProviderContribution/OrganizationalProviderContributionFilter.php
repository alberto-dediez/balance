<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class OrganizationalProviderContributionFilter
{
    use Filter;

    public function __construct(
        public ?CarbonFootprintReportSubjectType $subjectType = null,
        public ?string $carbonFootprintId = null,
        public ?AccountId $accountId = null,
        public ?ProviderContributionStatus $status = null,
        public ?ProviderContributionMode $mode = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
