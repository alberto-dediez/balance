<?php

namespace App\DataExchange\Domain\Model\Importation;

class ColumnValueTranslation
{
    public function __construct(
        public string $value,
        public ?string $translation
    ) {

    }

    public function __toString(): string
    {
        return $this->value;
    }
}
