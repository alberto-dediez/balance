<?php

namespace App\Property\Application\Destination\Import;

class ImportDestinationsResponse
{
    public function __construct(
        /** @var DestinationResponse[] $destinations */
        public array $destinations = [], 
        public int $matched = 0
    ) {
        
    }
}