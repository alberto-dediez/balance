<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryExchangeRateRepository implements ExchangeRateRepository
{
    /** @param array<string, ExchangeRate> $rates */
    public function __construct(
        private array $rates = [],
    ) {}

    public function add(ExchangeRate $rate): void
    {
        $this->rates[(string) $rate->id()] = $rate;
    }

    public function find(ExchangeRateId $id): ?ExchangeRate
    {
        return $this->rates[(string) $id] ?? null;
    }

    public function findByCurrency(Unit $currency): ?ExchangeRate
    {
        foreach ($this->rates as $rate) {
            if ($rate->currency() === $currency) {
                return $rate;
            }
        }

        return null;
    }

    /** @return list<ExchangeRate> */
    public function findByFilter(ExchangeRateFilter $filter): array
    {
        $matching = [];

        foreach ($this->rates as $rate) {
            if (!$this->matches($rate, $filter)) {
                continue;
            }

            $matching[] = $rate;
        }

        $this->sort($matching, $filter);

        $offset = $filter->page * $filter->limit;

        if ($filter->limit > 0) {
            return array_slice($matching, $offset, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(ExchangeRateFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    private function matches(ExchangeRate $rate, ExchangeRateFilter $filter): bool
    {
        if (null !== $filter->currency && $rate->currency() !== $filter->currency) {
            return false;
        }

        return true;
    }

    /** @param list<ExchangeRate> $rates */
    private function sort(array &$rates, ExchangeRateFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($rates, static function (ExchangeRate $a, ExchangeRate $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'date' => $a->date() <=> $b->date(),
                'rate' => (float) $a->rate() <=> (float) $b->rate(),
                default => strcmp($a->currency()->value, $b->currency()->value),
            };

            return 0 !== $comparison ? $comparison * $way : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
