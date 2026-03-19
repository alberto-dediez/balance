<?php

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryWasteRepository implements WasteRepository
{
    public function __construct(private readonly LocaleGetter $localeGetter, private array $wastes = [])
    {

    }

    public function add(Waste $waste): void
    {
        if (isset($this->wastes[(string) $waste->id()])) {
            return ;
        }

        $waste->translations()->setLocale($this->localeGetter->locale());

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

    public function findByFilter(WasteFilter $filter): array
    {
        $results = [];

        if (count($this->wastes) === 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->wastes as $waste) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $waste->name()), strtolower($filter->name))
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
