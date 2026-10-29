<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class OrganizationalEmployeeParticipationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
