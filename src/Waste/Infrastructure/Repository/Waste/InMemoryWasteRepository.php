<?php

namespace App\Waste\Infrastructure\Repository\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryWasteRepository implements WasteRepository
{
    public function __construct(private array $wastes = [])
    {

    }

    public function add(Waste $waste): void
    {
        if (isset($this->wastes[(string) $waste->id()])) {
            return ;
        }

        $this->wastes[(string) $waste->id()] = $waste;
    }

    public function remove(Waste $waste): void
    {
        if (!isset($this->wastes[(string) $waste->id()])) {
            return ;
        }

        unset($this->wastes[(string) $waste->id()]);
    }

    public function find(WasteId $wasteId): ?Waste
    {
        return (isset($this->wastes[(string) $wasteId]) && false == $this->wastes[(string) $wasteId]->isRemoved()) 
            ? $this->wastes[(string) $wasteId]
            : null
        ;
    }

    public function findByName(string $name): ?Waste
    {
        if (count($this->wastes) == 0) {
            return null;
        }

        foreach ($this->wastes as $waste) {
            if ($waste->name() == $name) {
                return $this->find($waste->id());
            }
        }

        return null;
    }

    public function findByFilter(WasteFilter $filter): array
    {
        $results = [];

        if (count($this->wastes) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->wastes as $waste) {
            if (
                !empty($filter->name) 
                && false === strpos(strtolower($waste->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->isDangerous && $filter->isDangerous !== $waste->isDangerous()) {
                continue;
            }

            if (true == $waste->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $waste;
        }

        return $results;
    }

    public function totalResultsByFilter(WasteFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}