<?php

namespace App\DataExchange\Application\Importation\Execute\Extractor;

class AssignValueExtractor implements FieldExtractor
{
    public function __construct(private mixed $value)
    {

    }

    public function extractField(array $row): mixed
    {
        return $this->value;
    }
}
