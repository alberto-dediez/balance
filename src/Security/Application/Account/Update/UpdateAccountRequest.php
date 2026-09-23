<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Update;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class UpdateAccountRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public string $name,
        public bool $active,
        public ?EmissionFactorGroupId $emissionFactorGroupId = null,
        public bool $consultant = false,
        public ?AccountId $consultantAccountId = null,
        public bool $reportCanEditTexts = false,
        public bool $reportAutoReview = true,
    ) {}
}
