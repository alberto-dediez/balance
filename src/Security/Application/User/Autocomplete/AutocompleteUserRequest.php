<?php

namespace App\Security\Application\User\Autocomplete;

class AutocompleteUserRequest
{
    public function __construct(public string $query)
    {
        
    }    
}