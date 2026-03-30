<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

class ListPropertiesResponse
{
    /**
     * @param \App\Property\Domain\Model\Property\Property[] $properties
     */
    public function __construct(public array $properties = [], public int $totalResults = 0) {}
}
