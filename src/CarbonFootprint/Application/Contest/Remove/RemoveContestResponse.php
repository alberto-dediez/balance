<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Remove;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveContestResponse implements CommandResponse
{
    public function __construct(
        public Contest $contest,
    ) {}
}
