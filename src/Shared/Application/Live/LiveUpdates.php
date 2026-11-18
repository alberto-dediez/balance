<?php

declare(strict_types=1);

namespace App\Shared\Application\Live;

use App\Security\Domain\Model\User\UserId;

interface LiveUpdates
{
    public function changed(string $context): void;

    public function touched(string $key): void;

    public function notifyUser(UserId $userId): void;

    public function touchedForUser(UserId $userId, string $key): void;
}
