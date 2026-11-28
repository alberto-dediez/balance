<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Close;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Shared\Application\CommandBus\CommandResponse;

class CloseContestResponse implements CommandResponse
{
    public function __construct(
        public Contest $contest,
    ) {}
}
