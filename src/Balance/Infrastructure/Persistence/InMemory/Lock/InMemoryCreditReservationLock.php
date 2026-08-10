<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Lock;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCreditReservationLock implements CreditReservationLock
{
    /**
     * @template T
     *
     * @param callable(): T $reservation
     *
     * @return T
     */
    public function withLock(AccountId $accountId, ProjectId $projectId, callable $reservation): mixed
    {
        return $reservation();
    }
}
