<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Import;

class ImportDestinationsRequest
{
    public function __construct(
        /** @var DestinationRequest[] $destinations */
        public array $destinations,
    ) {}
}
