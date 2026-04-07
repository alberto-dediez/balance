<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

interface PackagingRepository
{
    public function add(Packaging $packaging): void;

    public function remove(Packaging $packaging): void;

    public function find(PackagingId $packagingId): ?Packaging;

    /** @return array<Packaging> */
    public function findByFilter(PackagingFilter $filter): array;

    public function totalResultsByFilter(PackagingFilter $filter): int;
}
