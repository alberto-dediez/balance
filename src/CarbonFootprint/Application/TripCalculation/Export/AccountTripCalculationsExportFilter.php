<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Export;

use App\Security\Domain\Model\Account\AccountId;

class AccountTripCalculationsExportFilter
{
    public function __construct(
        public ?AccountId $accountId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {}
}
