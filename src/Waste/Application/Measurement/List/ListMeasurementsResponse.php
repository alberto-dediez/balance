<?php

namespace App\Waste\Application\Measurement\List;

class ListMeasurementsResponse
{
    public function __construct(public array $measurements = [], public int $totalResults = 0)
    {
        
    }
}