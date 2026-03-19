<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute\Extractor;

interface FieldExtractor
{
    public function extractField(array $row): mixed;
}
