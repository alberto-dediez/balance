<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

interface ExportationRepository
{
    public function add(Exportation $exportation): void;

    public function remove(Exportation $exportation): void;

    public function find(ExportationId $exportationId): ?Exportation;

    /** @return Exportation[] */
    public function findExpired(\DateTimeImmutable $now): array;
}
