<?php

namespace App\Property\Application\Property\Import;

use App\Property\Application\Property\Import\Field;

class FieldExtractor
{
    public function __construct(private array $config = [])
    {

    }

    public function extract(Field $field, array $row)
    {
        if (!isset($this->config[$field->value]) || null === $this->config[$field->value]) {
            return null;
        }

        $index = $this->config[$field->value];

        return isset($row[$index]) ? $row[$index] : null;
    }

    public function hasField(Field $field): bool
    {
        return isset($this->config[$field->value]) && null !== $this->config[$field->value];
    }
}