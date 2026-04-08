<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

interface IncidenceRepository
{
    public function add(Incidence $incidence): void;

    public function remove(Incidence $incidence): void;

    public function find(IncidenceId $incidenceId): ?Incidence;

    /** @return array<Incidence> */
    public function findByFilter(IncidenceFilter $filter): array;

    public function totalResultsByFilter(IncidenceFilter $filter): int;
}
