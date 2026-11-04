<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class DistributorAssignmentFilter
{
    use Filter;

    /** @param list<PropertyId>|null $propertyIds */
    public function __construct(
        public ?AccountId $distributorAccountId = null,
        public ?AccountId $accountId = null,
        public ?array $propertyIds = null,
        public ?PropertyId $propertyId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
