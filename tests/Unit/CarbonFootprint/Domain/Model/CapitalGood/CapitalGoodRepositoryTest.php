<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CapitalGoodRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddCapitalGoodToRepository(): void
    {
        $repository = $this->createSUT();

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $this->assertNull($repository->find($capitalGood->id()));

        $repository->add($capitalGood);

        $recoveredCapitalGood = $repository->find($capitalGood->id());

        $this->assertNotNull($recoveredCapitalGood);
        $this->assertEquals($capitalGood->id(), $recoveredCapitalGood->id());
    }

    public function testRemoveCapitalGoodToRepository(): void
    {
        $repository = $this->createSUT();

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $repository->add($capitalGood);

        $recoveredCapitalGood = $repository->find($capitalGood->id());

        $this->assertNotNull($recoveredCapitalGood);
        $this->assertEquals($capitalGood->id(), $recoveredCapitalGood->id());

        $repository->remove($capitalGood);

        $this->assertNull($repository->find($capitalGood->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::fromName('Nombre específico'));

        $filter = new CapitalGoodFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CapitalGoodFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::fromName('Nombre específico'));

        $filter = new CapitalGoodFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $repository->add($capitalGood);

        $recoveredCapitalGood = $repository->find($capitalGood->id());

        $this->assertNotNull($recoveredCapitalGood);

        $recoveredCapitalGood->remove();

        $repository->add($capitalGood);

        $this->assertNull($repository->find($capitalGood->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $this->assertNull($repository->find($capitalGood->id()));

        $capitalGood->translate('translated-name', Locale::English);

        $repository->add($capitalGood);

        $recoveredCapitalGood = $repository->find($capitalGood->id());

        $this->assertNotNull($recoveredCapitalGood);
        $this->assertEquals($capitalGood->id(), $recoveredCapitalGood->id());
        $this->assertEquals('translated-name', $recoveredCapitalGood->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $capitalGood->translate('translated-name', Locale::English);

        $repository->add($capitalGood);

        $recoveredCapitalGoods = $repository->findByFilter(new CapitalGoodFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredCapitalGoods);
        $this->assertEquals($capitalGood->id(), $recoveredCapitalGoods[0]->id());
        $this->assertEquals('translated-name', $recoveredCapitalGoods[0]->name());
    }

    public function testFindByFilterWithCategory(): void
    {
        $repository = $this->createSUT();

        $repository->add(CapitalGoodMother::from('Consultoría', (string) CapitalGoodId::generate(), CapitalGoodCategory::Renovations));
        $repository->add(CapitalGoodMother::from('Publicidad', (string) CapitalGoodId::generate(), CapitalGoodCategory::FurnitureAndEquipment));
        $repository->add(CapitalGoodMother::from('Campaña', (string) CapitalGoodId::generate(), CapitalGoodCategory::FurnitureAndEquipment));

        $filter = new CapitalGoodFilter(category: CapitalGoodCategory::FurnitureAndEquipment);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(CapitalGoodMother::fromName('B'));
        $repository->add(CapitalGoodMother::fromName('C'));
        $repository->add(CapitalGoodMother::fromName('A'));

        $filter = new CapitalGoodFilter();

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter($filter)));

        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->capitalGood('A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->capitalGood('B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->capitalGood('C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new CapitalGoodFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->capitalGood('A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->capitalGood('B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->capitalGood('C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new CapitalGoodFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterPaginatesAndCountsTotal(): void
    {
        $repository = $this->createSUT();

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $repository->add(CapitalGoodMother::fromName($name));
        }

        $filter = new CapitalGoodFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame(['C', 'D'], $this->names($repository->findByFilter($filter)));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));
    }

    private function capitalGood(
        string $name,
        \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        ?\DateTimeImmutable $updatedAt = null,
    ): CapitalGood {
        return new CapitalGood(
            id: CapitalGoodId::generate(),
            name: $name,
            category: CapitalGoodCategory::Others,
            unit: Unit::Euro,
            registeredAt: $registeredAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param array<CapitalGood> $capitalGoods
     *
     * @return list<string>
     */
    private function names(array $capitalGoods): array
    {
        return array_values(array_map(static fn(CapitalGood $capitalGood): string => $capitalGood->name(), $capitalGoods));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): CapitalGoodRepository;
}
