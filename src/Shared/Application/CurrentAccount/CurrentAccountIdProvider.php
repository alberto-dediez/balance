<?php

declare(strict_types=1);

namespace App\Shared\Application\CurrentAccount;

use App\Security\Domain\Model\Account\AccountId;

interface CurrentAccountIdProvider
{
    public function currentAccountId(): ?AccountId;
}
