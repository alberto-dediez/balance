<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\List;

use App\Property\Domain\Model\Destination\Destination;

class ListDestinationsResponse
{
    /**
     * @param Destination[] $destinations
     */
    public function __construct(public array $destinations = [], public int $totalResults = 0) {}
}
