<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

interface ReasonRepository
{
    public function add(Reason $reason): void;

    public function remove(Reason $reason): void;

    public function find(ReasonId $reasonId): ?Reason;

    /** @return array<Reason> */
    public function findByFilter(ReasonFilter $filter): array;

    public function totalResultsByFilter(ReasonFilter $filter): int;
}
