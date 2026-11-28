<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\PurgePersonalData;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Application\CommandBus\Command;

class PurgeContestPersonalDataRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
    ) {}
}
