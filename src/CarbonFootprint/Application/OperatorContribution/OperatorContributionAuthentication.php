<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\Security\Domain\Model\User\User;

final class OperatorContributionAuthentication
{
    public function __construct(
        public readonly OperatorContribution $contribution,
        public readonly User $user,
    ) {}
}
