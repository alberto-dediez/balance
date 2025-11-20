<?php

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationDataRepository
{
    public function create(Importation $importation): void;

    public function totalRows(ImportationId $importationId): int;

    public function getColumns(ImportationId $importationId): array;

    public function getValues(ImportationId $importationId, int $column): array;

    public function getData(ImportationId $importationId, int $rowNumber): array;

    public function remove(ImportationId $importationId): void;
}
