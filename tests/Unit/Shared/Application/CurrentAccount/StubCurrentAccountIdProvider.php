<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CurrentAccount;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class StubCurrentAccountIdProvider implements CurrentAccountIdProvider
{
    public function __construct(private readonly ?AccountId $accountId = null) {}

    public function currentAccountId(): ?AccountId
    {
        return $this->accountId;
    }
}
