<?php

namespace App\DataExchange\Application\Importation\Execute\Extractor;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;

class MapExtractor implements FieldExtractor
{
    private array $translations = [];

    private string $columnName;

    public function __construct(ImportationColumnConfiguration $columnConfiguration)
    {
        $this->columnName = $columnConfiguration->name;

        if (count($columnConfiguration->translations) === 0) {
            return ;
        }

        foreach ($columnConfiguration->translations as $translation) {
            $this->translations[$translation->value] = $translation->translation;
        }
    }

    public function extractField(array $row): mixed
    {
        $value = $row[$this->columnName] ?? null;

        return $this->translations[$value] ?? null;
    }
}
