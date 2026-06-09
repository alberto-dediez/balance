<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

class ImportationColumnConfiguration
{
    /**
     * @param array<int|string, mixed> $values
     * @param ColumnValueTranslation[] $translations
     */
    public function __construct(
        public int $column,
        public string $name,
        public ?string $field,
        public array $values,
        public array $translations,
    ) {}
}
