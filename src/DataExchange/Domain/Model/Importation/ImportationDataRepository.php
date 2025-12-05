<?php

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationDataRepository
{
    public function create(Importation $importation): void;

    public function totalRows(ImportationId $importationId): int;

    public function getColumns(ImportationId $importationId): array;

    public function getValues(ImportationId $importationId, int $column): array;

    public function getAllConfigurations(ImportationId $importationId): array;

    public function getConfiguration(ImportationId $importationId, int $column): ImportationColumnConfiguration;

    public function saveConfiguration(ImportationId $importationId, ImportationColumnConfiguration $configuration): void;

    public function getData(ImportationId $importationId, int $rowNumber): ?array;

    public function saveError(ImportationId $importationId, int $rowNumber, ?string $error): void;

    public function getResult(ImportationId $importationId, int $rowNumber): ?array;

    public function remove(ImportationId $importationId): void;
}
