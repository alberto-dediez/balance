<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Import;

class FieldExtractor
{
    /**
     * @param array<string, int|string> $config
     */
    public function __construct(private array $config = []) {}

    /**
     * @param array<mixed> $row
     */
    public function extract(Field $field, array $row)
    {
        if (!isset($this->config[$field->value])) {
            return null;
        }

        $index = $this->config[$field->value];

        return $row[$index] ?? null;
    }

    public function hasField(Field $field): bool
    {
        return isset($this->config[$field->value]);
    }
}
