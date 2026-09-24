<?php

declare(strict_types=1);

namespace App\Shared\Application\CurrentTimezone;

interface CurrentTimezoneProvider
{
    public function currentTimezone(): \DateTimeZone;
}
