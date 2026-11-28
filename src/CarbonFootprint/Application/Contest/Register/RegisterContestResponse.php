<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Register;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterContestResponse implements CommandResponse
{
    public function __construct(
        public Contest $contest,
    ) {}
}
