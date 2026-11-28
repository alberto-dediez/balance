<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Remove;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Application\CommandBus\Command;

class RemoveContestRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
    ) {}
}
