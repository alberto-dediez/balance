<?php

declare(strict_types=1);

namespace App\Security\Application\User\Autocomplete;

class AutocompleteUserRequest
{
    public function __construct(public string $query) {}
}
