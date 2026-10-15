<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

interface CapitalGoodRepository
{
    public function add(CapitalGood $capitalGood): void;

    public function remove(CapitalGood $capitalGood): void;

    public function find(CapitalGoodId $capitalGoodId): ?CapitalGood;

    /** @return array<CapitalGood> */
    public function findByFilter(CapitalGoodFilter $filter): array;

    public function totalResultsByFilter(CapitalGoodFilter $filter): int;
}
