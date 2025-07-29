<?php

namespace App\Property\Application\Destination\Import;

class ImportDestinationsRequest
{
    public function __construct(
        /** @var DestinationRequest[] $destinations */
        public array $destinations,
    ) {

    }
}