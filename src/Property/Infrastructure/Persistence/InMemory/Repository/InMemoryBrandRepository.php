<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandFilter;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryBrandRepository implements BrandRepository
{
    public function __construct(private array $brands = []) {}

    public function add(Brand $brand): void
    {
        if (isset($this->brands[(string) $brand->id()])) {
            return;
        }

        $this->brands[(string) $brand->id()] = $brand;
    }

    public function remove(Brand $brand): void
    {
        if (!isset($this->brands[(string) $brand->id()])) {
            return;
        }

        unset($this->brands[(string) $brand->id()]);
    }

    public function find(BrandId $brandId): ?Brand
    {
        return (isset($this->brands[(string) $brandId]) && false == $this->brands[(string) $brandId]->isRemoved())
            ? $this->brands[(string) $brandId]
            : null
        ;
    }

    public function findByName(string $name): ?Brand
    {
        if (0 === count($this->brands)) {
            return null;
        }

        foreach ($this->brands as $brand) {
            if (null !== $brand->name() && strtolower($brand->name()) === strtolower($name)) {
                return $this->find($brand->id());
            }
        }

        return null;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Brand
    {
        if (0 === count($this->brands)) {
            return null;
        }

        $sourceId = $source->id($externalId);
        $attribute = $source->attribute();

        foreach ($this->brands as $brand) {
            if ($brand->externalId()->isEmpty()) {
                continue;
            }

            if ($brand->externalId()->$attribute() === $sourceId) {
                return $this->find($brand->id());
            }
        }

        return null;
    }

    public function findByFilter(BrandFilter $filter): array
    {
        $results = [];

        if (0 === count($this->brands)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->brands as $brand) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $brand->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $brand->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $brand;
        }

        return $results;
    }

    public function totalResultsByFilter(BrandFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
