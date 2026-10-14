<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\List;

class ListEmployeesResponse
{
    /** @param array<\App\HumanResources\Domain\Model\Employee\Employee> $employees */
    public function __construct(public array $employees = [], public int $totalResults = 0) {}
}
