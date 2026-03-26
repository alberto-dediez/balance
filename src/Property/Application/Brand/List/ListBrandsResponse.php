<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\List;

class ListBrandsResponse
{
    /**
     * @param \App\Property\Domain\Model\Brand\Brand[] $brands
     */
    public function __construct(public array $brands = [], public int $totalResults = 0) {}
}
