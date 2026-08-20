<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Autocomplete;

class AutocompleteAccountRequest
{
    public function __construct(public string $query) {}
}
