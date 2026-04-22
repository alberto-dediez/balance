<?php

declare(strict_types=1);

namespace App\Property\Application\Company\List;

class ListCompaniesResponse
{
    /** @param array<\App\Property\Domain\Model\Company\Company> $companies */
    public function __construct(public array $companies = [], public int $totalResults = 0) {}
}
