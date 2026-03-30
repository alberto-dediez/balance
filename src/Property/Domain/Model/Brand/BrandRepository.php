<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Brand;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

interface BrandRepository
{
    public function add(Brand $brand): void;

    public function remove(Brand $brand): void;

    public function find(BrandId $brandId): ?Brand;

    public function findByName(string $name): ?Brand;

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Brand;

    /**
     * @return Brand[]
     */
    public function findByFilter(BrandFilter $filter): array;

    public function totalResultsByFilter(BrandFilter $filter): int;
}
