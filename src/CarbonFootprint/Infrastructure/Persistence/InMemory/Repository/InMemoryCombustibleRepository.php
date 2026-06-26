<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCombustibleRepository implements CombustibleRepository
{
    /** @param array<string, Combustible> $combustibles */
    public function __construct(
        private array $combustibles = [],
    ) {}

    public function add(Combustible $combustible): void
    {
        if (isset($this->combustibles[(string) $combustible->id()])) {
            return;
        }

        $this->combustibles[(string) $combustible->id()] = $combustible;
    }

    public function remove(Combustible $combustible): void
    {
        if (!isset($this->combustibles[(string) $combustible->id()])) {
            return;
        }

        unset($this->combustibles[(string) $combustible->id()]);
    }

    public function find(CombustibleId $combustibleId): ?Combustible
    {
        return (isset($this->combustibles[(string) $combustibleId]) && false == $this->combustibles[(string) $combustibleId]->isRemoved())
            ? $this->combustibles[(string) $combustibleId]
            : null
        ;
    }

    public function findOneByName(string $name): ?Combustible
    {
        foreach ($this->combustibles as $combustible) {
            if (true == $combustible->isRemoved()) {
                continue;
            }

            if (strtolower(trim((string) $combustible->name())) === strtolower(trim($name))) {
                return $combustible;
            }
        }

        return null;
    }

    /** @return array<int, Combustible> */
    public function findByFilter(CombustibleFilter $filter): array
    {
        $results = [];

        if (0 === count($this->combustibles)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->combustibles as $combustible) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $combustible->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $combustible->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $combustible;
        }

        return $results;
    }

    public function totalResultsByFilter(CombustibleFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
