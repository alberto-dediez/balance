<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProjectContributionCreditAllocationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
