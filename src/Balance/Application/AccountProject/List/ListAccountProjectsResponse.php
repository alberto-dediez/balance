<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\List;

class ListAccountProjectsResponse
{
    /** @param array<\App\Balance\Domain\Model\AccountProject\AccountProject> $accountProjects */
    public function __construct(public array $accountProjects = [], public int $totalResults = 0) {}
}
