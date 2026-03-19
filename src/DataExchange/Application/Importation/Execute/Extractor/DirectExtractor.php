<?php

namespace App\DataExchange\Application\Importation\Execute\Extractor;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;

class DirectExtractor implements FieldExtractor
{
    private readonly string $columnName;

    public function __construct(ImportationColumnConfiguration $columnConfiguration)
    {
        $this->columnName = $columnConfiguration->name;
    }

    public function extractField(array $row): mixed
    {
        return $row[$this->columnName] ?? null;
    }
}
