<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;

class SpyImpersonatedAccount extends ImpersonatedAccount
{
    /** @var list<array{0: ?AccountId, 1: ?UserId}> */
    private array $forceCalls = [];

    public function __construct() {}

    public function force(?AccountId $accountId, ?UserId $impersonatorId): void
    {
        $this->forceCalls[] = [$accountId, $impersonatorId];
    }

    /** @return list<array{0: ?AccountId, 1: ?UserId}> */
    public function forceCalls(): array
    {
        return $this->forceCalls;
    }
}
