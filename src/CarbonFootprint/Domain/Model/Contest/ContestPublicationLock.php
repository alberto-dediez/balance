<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\Security\Domain\Model\Account\AccountId;

interface ContestPublicationLock
{
    public function acquire(AccountId $accountId): void;
}
