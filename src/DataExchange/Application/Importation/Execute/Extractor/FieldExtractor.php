<?php

namespace App\DataExchange\Application\Importation\Execute\Extractor;

interface FieldExtractor
{
    public function extractField(array $row): mixed;
}
