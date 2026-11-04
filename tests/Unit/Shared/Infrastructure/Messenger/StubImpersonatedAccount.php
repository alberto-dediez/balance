<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;

class StubImpersonatedAccount extends ImpersonatedAccount
{
    private ?AccountId $accountId = null;

    public function __construct() {}

    public function setAccountId(?AccountId $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }
}
