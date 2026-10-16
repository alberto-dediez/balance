<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EconomicSectorRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddEconomicSectorToRepository(): void
    {
        $repository = $this->createSUT();

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $this->assertNull($repository->find($economicSector->id()));

        $repository->add($economicSector);

        $recoveredEconomicSector = $repository->find($economicSector->id());

        $this->assertNotNull($recoveredEconomicSector);
        $this->assertEquals($economicSector->id(), $recoveredEconomicSector->id());
    }

    public function testRemoveEconomicSectorToRepository(): void
    {
        $repository = $this->createSUT();

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $repository->add($economicSector);

        $recoveredEconomicSector = $repository->find($economicSector->id());

        $this->assertNotNull($recoveredEconomicSector);
        $this->assertEquals($economicSector->id(), $recoveredEconomicSector->id());

        $repository->remove($economicSector);

        $this->assertNull($repository->find($economicSector->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::fromName('Nombre específico'));

        $filter = new EconomicSectorFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new EconomicSectorFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::fromName('Nombre específico'));

        $filter = new EconomicSectorFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $repository->add($economicSector);

        $recoveredEconomicSector = $repository->find($economicSector->id());

        $this->assertNotNull($recoveredEconomicSector);

        $recoveredEconomicSector->remove();

        $repository->add($economicSector);

        $this->assertNull($repository->find($economicSector->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $this->assertNull($repository->find($economicSector->id()));

        $economicSector->translate('translated-name', Locale::English);

        $repository->add($economicSector);

        $recoveredEconomicSector = $repository->find($economicSector->id());

        $this->assertNotNull($recoveredEconomicSector);
        $this->assertEquals($economicSector->id(), $recoveredEconomicSector->id());
        $this->assertEquals('translated-name', $recoveredEconomicSector->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $economicSector->translate('translated-name', Locale::English);

        $repository->add($economicSector);

        $recoveredEconomicSectors = $repository->findByFilter(new EconomicSectorFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredEconomicSectors);
        $this->assertEquals($economicSector->id(), $recoveredEconomicSectors[0]->id());
        $this->assertEquals('translated-name', $recoveredEconomicSectors[0]->name());
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(EconomicSectorMother::fromName('B'));
        $repository->add(EconomicSectorMother::fromName('C'));
        $repository->add(EconomicSectorMother::fromName('A'));

        $filter = new EconomicSectorFilter();

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter($filter)));

        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->economicSector('A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->economicSector('B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->economicSector('C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new EconomicSectorFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->economicSector('A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->economicSector('B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->economicSector('C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new EconomicSectorFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterWithClassificationCodePrefixIgnoringCase(): void
    {
        $repository = $this->createSUT();

        $repository->add(EconomicSectorMother::withCode('25.1A'));
        $repository->add(EconomicSectorMother::withCode('25.2B'));
        $repository->add(EconomicSectorMother::withCode('62.01'));
        $repository->add(EconomicSectorMother::random());

        $this->assertCount(2, $repository->findByFilter(new EconomicSectorFilter(classificationCode: '25')));
        $this->assertCount(1, $repository->findByFilter(new EconomicSectorFilter(classificationCode: '25.1a')));
        $this->assertCount(0, $repository->findByFilter(new EconomicSectorFilter(classificationCode: '01')));
    }

    public function testFindByClassificationCode(): void
    {
        $repository = $this->createSUT();

        $economicSector = EconomicSectorMother::withCode('25');
        $repository->add($economicSector);
        $repository->add(EconomicSectorMother::withCode('251'));

        $found = $repository->findByClassificationCode('25');

        $this->assertNotNull($found);
        $this->assertEquals($economicSector->id(), $found->id());
        $this->assertNull($repository->findByClassificationCode('2'));
        $this->assertNull($repository->findByClassificationCode('99'));
    }

    public function testFindByClassificationCodeIgnoresRemoved(): void
    {
        $repository = $this->createSUT();

        $economicSector = EconomicSectorMother::withCode('25');
        $repository->add($economicSector);

        $economicSector->remove();
        $repository->add($economicSector);

        $this->assertNull($repository->findByClassificationCode('25'));
    }

    public function testFindByFilterOrdersByClassificationCode(): void
    {
        $repository = $this->createSUT();

        $repository->add(EconomicSectorMother::from(name: 'A', id: (string) EconomicSectorId::generate(), classificationCode: '30'));
        $repository->add(EconomicSectorMother::from(name: 'B', id: (string) EconomicSectorId::generate(), classificationCode: '10'));
        $repository->add(EconomicSectorMother::from(name: 'C', id: (string) EconomicSectorId::generate(), classificationCode: '20'));

        $filter = new EconomicSectorFilter();
        $filter->orderBy = 'classificationCode';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterPaginatesAndCountsTotal(): void
    {
        $repository = $this->createSUT();

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $repository->add(EconomicSectorMother::fromName($name));
        }

        $filter = new EconomicSectorFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame(['C', 'D'], $this->names($repository->findByFilter($filter)));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));
    }

    private function economicSector(
        string $name,
        \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        ?\DateTimeImmutable $updatedAt = null,
    ): EconomicSector {
        return new EconomicSector(
            id: EconomicSectorId::generate(),
            name: $name,
            unit: Unit::Euro,
            registeredAt: $registeredAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param array<EconomicSector> $economicSectors
     *
     * @return list<string>
     */
    private function names(array $economicSectors): array
    {
        return array_values(array_map(static fn(EconomicSector $economicSector): string => $economicSector->name(), $economicSectors));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): EconomicSectorRepository;
}
