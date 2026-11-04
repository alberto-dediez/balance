<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\Register;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterDistributorAssignmentResponse implements CommandResponse
{
    public function __construct(
        public DistributorAssignmentId $id,
        public ?AccountId $distributorAccountId = null,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
    ) {}
}
