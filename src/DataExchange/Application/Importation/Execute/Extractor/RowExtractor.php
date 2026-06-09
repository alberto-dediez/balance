<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute\Extractor;

class RowExtractor
{
    /** @var array<string, FieldExtractor> */
    private array $fields = [];

    public function addFieldExtractor(FieldExtractor $fieldExtractor, string $fieldName): self
    {
        $this->fields[$fieldName] = $fieldExtractor;

        return $this;
    }

    /**
     * @param array<int|string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function extractFields(array $row): array
    {
        if (0 === count($this->fields)) {
            return [];
        }

        $result = [];

        foreach ($this->fields as $fieldName => $fieldExtractor) {
            $result[$fieldName] = $fieldExtractor->extractField($row);
        }

        return $result;
    }
}
