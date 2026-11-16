<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\CreditPurchase\Balance\CreditReservationLock;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;

class FakeCreditReservationLock implements CreditReservationLock
{
    /** @var array<int, array{AccountId, ProjectId}> */
    private array $locks = [];

    /**
     * @param ?\Closure(): void $beforeRelease     lets a test observe the state written while the lock is held
     * @param ?\Closure(): void $beforeReservation
     */
    public function __construct(
        private ?\Closure $beforeRelease = null,
        private ?\Closure $beforeReservation = null,
    ) {}

    /**
     * @template T
     *
     * @param callable(): T $reservation
     *
     * @return T
     */
    public function withLock(AccountId $accountId, ProjectId $projectId, callable $reservation): mixed
    {
        $this->locks[] = [$accountId, $projectId];

        if ($this->beforeReservation instanceof \Closure) {
            ($this->beforeReservation)();
        }

        try {
            return $reservation();
        } finally {
            if ($this->beforeRelease instanceof \Closure) {
                ($this->beforeRelease)();
            }
        }
    }

    /** @return array<int, array{AccountId, ProjectId}> */
    public function locks(): array
    {
        return $this->locks;
    }
}
