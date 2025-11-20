<?php

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationRepository
{
    public function save(Importation $importation): void;

    public function find(ImportationId $importationId): Importation;

    public function getColumns(ImportationId $importationId): array;

    public function getValues(ImportationId $importationId, int $column): array;

    public function getData(ImportationId $importationId, int $rowNumber): array;

    public function remove(Importation $importation): void;
}
