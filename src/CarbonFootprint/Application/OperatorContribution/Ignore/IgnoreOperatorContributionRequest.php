<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Ignore;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\Shared\Application\CommandBus\Command;

class IgnoreOperatorContributionRequest implements Command
{
    public function __construct(public OperatorContributionId $contributionId) {}
}
