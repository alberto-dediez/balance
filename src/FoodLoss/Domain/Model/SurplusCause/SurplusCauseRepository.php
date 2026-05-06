<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

interface SurplusCauseRepository
{
    public function add(SurplusCause $surplusCause): void;

    public function remove(SurplusCause $surplusCause): void;

    public function find(SurplusCauseId $surplusCauseId): ?SurplusCause;

    /** @return array<SurplusCause> */
    public function findByFilter(SurplusCauseFilter $filter): array;

    public function totalResultsByFilter(SurplusCauseFilter $filter): int;
}
