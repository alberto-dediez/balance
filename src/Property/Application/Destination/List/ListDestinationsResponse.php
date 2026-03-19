<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\List;

class ListDestinationsResponse
{
    public function __construct(public array $destinations = [], public int $totalResults = 0) {}
}
