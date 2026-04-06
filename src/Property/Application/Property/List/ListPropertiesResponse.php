<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

use App\Property\Domain\Model\Property\Property;

class ListPropertiesResponse
{
    /**
     * @param Property[] $properties
     */
    public function __construct(public array $properties = [], public int $totalResults = 0) {}
}
