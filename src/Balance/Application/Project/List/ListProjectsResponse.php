<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\List;

class ListProjectsResponse
{
    /** @param array<\App\Balance\Domain\Model\Project\Project> $projects */
    public function __construct(public array $projects = [], public int $totalResults = 0) {}
}
