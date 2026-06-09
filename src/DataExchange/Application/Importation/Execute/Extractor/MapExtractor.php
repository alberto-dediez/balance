<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute\Extractor;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;

class MapExtractor implements FieldExtractor
{
    /** @var array<string, string|null> */
    private array $translations = [];

    private readonly string $columnName;

    public function __construct(ImportationColumnConfiguration $columnConfiguration)
    {
        $this->columnName = $columnConfiguration->name;

        if (0 === count($columnConfiguration->translations)) {
            return;
        }

        foreach ($columnConfiguration->translations as $translation) {
            $this->translations[$translation->value] = $translation->translation;
        }
    }

    /** @param array<int|string, mixed> $row */
    public function extractField(array $row): mixed
    {
        $value = $row[$this->columnName] ?? null;

        return $this->translations[$value] ?? null;
    }
}
