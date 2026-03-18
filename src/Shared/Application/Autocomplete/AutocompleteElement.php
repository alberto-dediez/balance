<?php

declare(strict_types=1);

namespace App\Shared\Application\Autocomplete;

class AutocompleteElement
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}
}
