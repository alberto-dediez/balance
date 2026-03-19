<?php

declare(strict_types=1);

namespace App\Property\Application\Property\List;

class ListPropertiesResponse
{
    public function __construct(public array $properties = [], public int $totalResults = 0) {}
}
