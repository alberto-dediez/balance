<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\PurgePersonalData;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Shared\Application\CommandBus\CommandResponse;

class PurgeContestPersonalDataResponse implements CommandResponse
{
    public function __construct(
        public Contest $contest,
    ) {}
}
