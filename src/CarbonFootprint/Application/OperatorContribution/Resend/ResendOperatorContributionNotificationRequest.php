<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Resend;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\Shared\Application\CommandBus\Command;

class ResendOperatorContributionNotificationRequest implements Command
{
    public function __construct(public OperatorContributionId $contributionId) {}
}
