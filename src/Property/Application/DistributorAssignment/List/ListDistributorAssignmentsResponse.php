<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\List;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;

class ListDistributorAssignmentsResponse
{
    /**
     * @param DistributorAssignment[] $assignments
     */
    public function __construct(public array $assignments = [], public int $totalResults = 0) {}
}
