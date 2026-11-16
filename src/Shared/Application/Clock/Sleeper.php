<?php

declare(strict_types=1);

namespace App\Shared\Application\Clock;

interface Sleeper
{
    public function sleep(int $milliseconds): void;
}
