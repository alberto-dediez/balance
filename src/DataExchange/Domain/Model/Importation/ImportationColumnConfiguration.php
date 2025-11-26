<?php

namespace App\DataExchange\Domain\Model\Importation;

class ImportationColumnConfiguration
{
    public function __construct(
        public int $column,
        public string $name,
        public ?string $field,
        public array $values,
        public array $translations
    ) {

    }
}
