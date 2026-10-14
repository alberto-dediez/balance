<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Import;

class ImportDestinationsResponse
{
    public function __construct(
        /** @var DestinationResponse[] $destinations */
        public array $destinations = [],
        public int $matched = 0,
    ) {}
}
