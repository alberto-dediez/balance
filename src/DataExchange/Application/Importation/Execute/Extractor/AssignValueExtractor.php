<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute\Extractor;

class AssignValueExtractor implements FieldExtractor
{
    public function __construct(private readonly mixed $value) {}

    /** @param array<int|string, mixed> $row */
    public function extractField(array $row): mixed
    {
        return $this->value;
    }
}
