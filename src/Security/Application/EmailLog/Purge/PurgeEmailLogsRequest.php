<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Purge;

use App\Shared\Application\CommandBus\Command;

class PurgeEmailLogsRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
