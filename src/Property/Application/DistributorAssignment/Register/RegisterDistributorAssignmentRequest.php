<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\Register;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterDistributorAssignmentRequest implements Command
{
    public function __construct(
        public AccountId $distributorAccountId,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
    ) {}
}
