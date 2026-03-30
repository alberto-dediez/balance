<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\List;

use App\Waste\Domain\Model\Container\Container;

class ListContainersResponse
{
    /** @param array<int, Container> $containers */
    public function __construct(public array $containers = [], public int $totalResults = 0) {}
}
