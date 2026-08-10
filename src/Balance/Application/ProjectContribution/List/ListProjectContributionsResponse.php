<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\List;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;

class ListProjectContributionsResponse
{
    /** @param array<ProjectContribution> $projectContributions */
    public function __construct(public array $projectContributions = [], public int $totalResults = 0) {}
}
