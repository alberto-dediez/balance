<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\Remove;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Shared\Application\CommandBus\Command;

class RemoveDistributorAssignmentRequest implements Command
{
    public function __construct(
        public DistributorAssignmentId $id,
        public bool $notifyDistributor = true,
    ) {}
}
