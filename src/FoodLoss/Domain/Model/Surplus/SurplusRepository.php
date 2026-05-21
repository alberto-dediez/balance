<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

interface SurplusRepository
{
    public function add(Surplus $surplus): void;

    public function remove(Surplus $surplus): void;

    public function find(SurplusId $surplusId): ?Surplus;

    /** @return array<Surplus> */
    public function findByFilter(SurplusFilter $filter): array;

    public function totalResultsByFilter(SurplusFilter $filter): int;
}
