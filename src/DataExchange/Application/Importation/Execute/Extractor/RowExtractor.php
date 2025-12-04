<?php

namespace App\DataExchange\Application\Importation\Execute\Extractor;

class RowExtractor
{
    private array $fields = [];

    public function __construct()
    {

    }

    public function addFieldExtractor(FieldExtractor $fieldExtractor, string $fieldName): self
    {
        $this->fields[$fieldName] = $fieldExtractor;

        return $this;
    }

    public function extractFields(array $row): array
    {
        if (count($this->fields) === 0) {
            return [];
        }

        $result = [];

        foreach ($this->fields as $fieldName => $fieldExtractor) {
            $result[$fieldName] = $fieldExtractor->extractField($row);
        }

        return $result;
    }
}
