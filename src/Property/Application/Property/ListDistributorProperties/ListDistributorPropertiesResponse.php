<?php

declare(strict_types=1);

namespace App\Property\Application\Property\ListDistributorProperties;

use App\Property\Domain\Model\Property\Property;

class ListDistributorPropertiesResponse
{
    /**
     * @param Property[] $properties
     */
    public function __construct(public array $properties = [], public int $totalResults = 0) {}
}
