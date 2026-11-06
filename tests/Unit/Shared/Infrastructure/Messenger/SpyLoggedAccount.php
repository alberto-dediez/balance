<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Infrastructure\Symfony\Security\LoggedAccount;

class SpyLoggedAccount extends LoggedAccount
{
    private int $resetCalls = 0;

    public function __construct() {}

    public function reset(): void
    {
        ++$this->resetCalls;
    }

    public function resetCalls(): int
    {
        return $this->resetCalls;
    }
}
