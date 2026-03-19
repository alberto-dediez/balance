<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

class ImportationColumnConfiguration
{
    public function __construct(
        public int $column,
        public string $name,
        public ?string $field,
        public array $values,
        /** @var ColumnValueTranslation[] $translations */
        public array $translations,
    ) {}
}
