<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\List;

use App\Property\Domain\Model\Brand\Brand;

class ListBrandsResponse
{
    /**
     * @param Brand[] $brands
     */
    public function __construct(public array $brands = [], public int $totalResults = 0) {}
}
