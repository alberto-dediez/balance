<?php

namespace App\Waste\Application\Container\List;

class ListContainersResponse
{
    public function __construct(public array $containers = [], public int $totalResults = 0)
    {
        
    }
}