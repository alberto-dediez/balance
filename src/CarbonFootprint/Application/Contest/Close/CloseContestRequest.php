<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Close;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Application\CommandBus\Command;

class CloseContestRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
    ) {}
}
