<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationRepository
{
    public function add(Importation $importation): void;

    public function find(ImportationId $importationId): ?Importation;

    /** @return array<string, mixed>|null */
    public function getData(ImportationId $importationId, int $rowNumber): ?array;

    public function saveResult(ImportationId $importationId, int $rowNumber, ?string $error): void;

    /** @return array<string, mixed>|null */
    public function getResult(ImportationId $importationId, int $rowNumber): ?array;

    public function remove(Importation $importation): void;

    public function pendingRows(ImportationId $importationId): int;
}
