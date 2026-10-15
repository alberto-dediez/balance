<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ExchangeRateRepositoryTest extends KernelTestCase
{
    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $exchangeRate = ExchangeRateMother::registered();

        $this->assertNull($repository->find($exchangeRate->id()));

        $repository->add($exchangeRate);

        $recovered = $repository->find($exchangeRate->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($exchangeRate->id(), $recovered->id());
    }

    public function testFindByCurrency(): void
    {
        $repository = $this->createSUT();

        $repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar));
        $repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, rate: '0.80'));

        $this->assertSame(Unit::UsDollar, $repository->findByCurrency(Unit::UsDollar)?->currency());
        $this->assertSame('0.80', $repository->findByCurrency(Unit::PoundSterling)?->rate());
        $this->assertNull($repository->findByCurrency(Unit::JapaneseYen));
    }

    public function testFindByFilterByCurrency(): void
    {
        $repository = $this->createSUT();

        $repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar));
        $repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, rate: '0.80'));

        $result = $repository->findByFilter(new ExchangeRateFilter(currency: Unit::PoundSterling));

        $this->assertCount(1, $result);
        $this->assertSame(Unit::PoundSterling, $result[0]->currency());
        $this->assertSame(1, $repository->totalResultsByFilter(new ExchangeRateFilter(currency: Unit::PoundSterling)));
        $this->assertSame(2, $repository->totalResultsByFilter(new ExchangeRateFilter()));
    }

    public function testFindByFilterSortsByCurrencyByDefault(): void
    {
        $repository = $this->createSUT();

        $repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));
        $repository->add(ExchangeRateMother::registered(currency: Unit::AustralianDollar, rate: '1.70'));
        $repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, rate: '0.80'));

        $this->assertSame(
            [Unit::AustralianDollar, Unit::PoundSterling, Unit::UsDollar],
            $this->currencies($repository->findByFilter(new ExchangeRateFilter())),
        );
    }

    public function testFindByFilterSortsByRateAndDate(): void
    {
        $repository = $this->createSUT();

        $repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar, date: '2026-10-13', rate: '1.25'));
        $repository->add(ExchangeRateMother::registered(currency: Unit::AustralianDollar, date: '2026-10-12', rate: '1.70'));
        $repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, date: '2026-10-14', rate: '0.80'));

        $byRate = new ExchangeRateFilter();
        $byRate->orderBy = 'rate';
        $byRate->orderWay = 'DESC';

        $this->assertSame(
            [Unit::AustralianDollar, Unit::UsDollar, Unit::PoundSterling],
            $this->currencies($repository->findByFilter($byRate)),
        );

        $byDate = new ExchangeRateFilter();
        $byDate->orderBy = 'date';

        $this->assertSame(
            [Unit::AustralianDollar, Unit::UsDollar, Unit::PoundSterling],
            $this->currencies($repository->findByFilter($byDate)),
        );
    }

    public function testFindByFilterPaginates(): void
    {
        $repository = $this->createSUT();

        $repository->add(ExchangeRateMother::registered(currency: Unit::UsDollar));
        $repository->add(ExchangeRateMother::registered(currency: Unit::AustralianDollar));
        $repository->add(ExchangeRateMother::registered(currency: Unit::PoundSterling));

        $filter = new ExchangeRateFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame([Unit::UsDollar], $this->currencies($repository->findByFilter($filter)));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
        $this->assertCount(3, $repository->findByFilter(new ExchangeRateFilter(limit: 0)));
    }

    abstract protected function createSUT(): ExchangeRateRepository;

    /**
     * @param list<ExchangeRate> $exchangeRates
     *
     * @return list<Unit>
     */
    private function currencies(array $exchangeRates): array
    {
        return array_map(static fn(ExchangeRate $exchangeRate): Unit => $exchangeRate->currency(), $exchangeRates);
    }
}
