<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Autocomplete;

class AutocompleteDestinationRequest
{
    public function __construct(public string $query) {}
}
