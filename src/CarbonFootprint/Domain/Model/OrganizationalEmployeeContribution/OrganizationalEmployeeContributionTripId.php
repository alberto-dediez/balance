<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class OrganizationalEmployeeContributionTripId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
