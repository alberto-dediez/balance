<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Update;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateContestResponse implements CommandResponse
{
    public function __construct(
        public Contest $contest,
    ) {}
}
