<?php

namespace App\DataExchange\Domain\Model\Importation;

interface ImportationRepository
{
    public function add(Importation $importation): void;

    public function find(ImportationId $importationId): ?Importation;

    public function getData(ImportationId $importationId, int $rowNumber): ?array;
    public function saveResult(ImportationId $importationId, int $rowNumber, ?string $error): void;
    public function getResult(ImportationId $importationId, int $rowNumber): ?array;

    public function remove(Importation $importation): void;
}
