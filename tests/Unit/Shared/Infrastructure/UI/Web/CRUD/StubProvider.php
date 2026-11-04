<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\CRUD;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

final class StubProvider implements CurrentAccountIdProvider
{
    public function __construct(private readonly ?AccountId $accountId) {}

    public function currentAccountId(): ?AccountId
    {
        return $this->accountId;
    }
}
