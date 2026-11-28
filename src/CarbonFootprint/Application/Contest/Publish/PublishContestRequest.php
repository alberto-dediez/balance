<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Publish;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Application\CommandBus\Command;

class PublishContestRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
        public bool $responsibilityAccepted = false,
    ) {}
}
