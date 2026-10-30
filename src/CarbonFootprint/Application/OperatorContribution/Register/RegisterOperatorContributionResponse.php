<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Register;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterOperatorContributionResponse implements CommandResponse
{
    public function __construct(public OperatorContributionId $contributionId) {}
}
