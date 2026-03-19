<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\List;

class ListContainersResponse
{
    public function __construct(public array $containers = [], public int $totalResults = 0) {}
}
