<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\Security\Domain\Model\Account\AccountId;

class FakeContestPublicationLock implements ContestPublicationLock
{
    /** @param \Closure(): void $onAcquire */
    public function __construct(private \Closure $onAcquire) {}

    public function acquire(AccountId $accountId): void
    {
        ($this->onAcquire)();
    }
}
