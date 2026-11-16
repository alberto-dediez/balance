<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Application\Clock\Sleeper;

class SystemSleeper implements Sleeper
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
