<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;

/**
 * Port that serializes the credit reservations of an account on a project: reading the availability
 * and writing the contribution that consumes it must happen as a single non-interleaved step.
 */
interface CreditReservationLock
{
    /**
     * @template T
     *
     * @param callable(): T $reservation
     *
     * @return T
     */
    public function withLock(AccountId $accountId, ProjectId $projectId, callable $reservation): mixed;
}
