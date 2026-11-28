<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\GetActiveForHost;

use App\CarbonFootprint\Domain\Model\Contest\Contest;

class GetActiveContestForHostResponse
{
    public function __construct(
        public ?Contest $contest = null,
    ) {}
}
