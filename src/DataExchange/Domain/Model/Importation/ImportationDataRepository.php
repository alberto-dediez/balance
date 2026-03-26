<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationDataRepository
{
    public function create(Importation $importation): void;

    public function pendingRows(ImportationId $importationId): int;

    /** @return array<int, string> */
    public function getColumns(ImportationId $importationId): array;

    /** @return array<int, string> */
    public function getValues(ImportationId $importationId, int $column): array;

    /** @return ImportationColumnConfiguration[] */
    public function getAllConfigurations(ImportationId $importationId): array;

    public function getConfiguration(ImportationId $importationId, int $column): ImportationColumnConfiguration;

    public function saveConfiguration(ImportationId $importationId, ImportationColumnConfiguration $configuration): void;

    /** @return array<string, mixed>|null */
    public function getData(ImportationId $importationId, int $rowNumber): ?array;

    public function saveResult(ImportationId $importationId, int $rowNumber, ?string $error): void;

    /** @return array<string, mixed>|null */
    public function getResult(ImportationId $importationId, int $rowNumber): ?array;

    public function remove(ImportationId $importationId): void;
}
