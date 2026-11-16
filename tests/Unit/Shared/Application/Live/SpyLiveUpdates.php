<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Live;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Live\LiveUpdates;

class SpyLiveUpdates implements LiveUpdates
{
    /** @var list<string> */
    public array $changed = [];

    /** @var list<string> */
    public array $touched = [];

    /** @var list<UserId> */
    public array $notifiedUsers = [];

    /** @var list<array{UserId, string}> */
    public array $touchedForUsers = [];

    public function changed(string $context): void
    {
        $this->changed[] = $context;
    }

    public function touched(string $key): void
    {
        $this->touched[] = $key;
    }

    public function notifyUser(UserId $userId): void
    {
        $this->notifiedUsers[] = $userId;
    }

    public function touchedForUser(UserId $userId, string $key): void
    {
        $this->touchedForUsers[] = [$userId, $key];
    }
}
