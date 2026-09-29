<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MaterialitySectorRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
        parent::tearDown();
    }

    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();

        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();

        $this->assertNull($repository->find($materialitySector->id()));

        $repository->add($materialitySector);

        $recovered = $repository->find($materialitySector->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($materialitySector->id(), $recovered->id());
        $this->assertSame('materiality-sector-name', $recovered->name());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();

        $repository->add($materialitySector);
        $repository->remove($materialitySector);

        $this->assertNull($repository->find($materialitySector->id()));
    }

    public function testFindDoesNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();

        $repository->add($materialitySector);

        $materialitySector->remove();

        $repository->add($materialitySector);

        $this->assertNull($repository->find($materialitySector->id()));
    }

    public function testFindRecoversTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();
        $materialitySector->translate('translated-name', Locale::English);

        $repository->add($materialitySector);

        $recovered = $repository->find($materialitySector->id());

        $this->assertNotNull($recovered);
        $this->assertSame('translated-name', $recovered->name());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialitySectorMother::fromName('Hoteles'));
        $repository->add(MaterialitySectorMother::fromName('Eventos'));
        $repository->add(MaterialitySectorMother::fromName('Restauración'));

        $this->assertCount(3, $repository->findByFilter(new MaterialitySectorFilter()));
        $this->assertCount(1, $repository->findByFilter(new MaterialitySectorFilter(name: 'Hotel')));
        $this->assertCount(0, $repository->findByFilter(new MaterialitySectorFilter(name: 'Transporte')));
        $this->assertCount(2, $repository->findByFilter(new MaterialitySectorFilter(limit: 2)));
        $this->assertCount(1, $repository->findByFilter($this->pagedFilter(page: 1, limit: 2)));
        $this->assertSame(3, $repository->totalResultsByFilter(new MaterialitySectorFilter(limit: 1)));
        $this->assertSame(1, $repository->totalResultsByFilter(new MaterialitySectorFilter(name: 'Hotel')));
    }

    public function testFindByFilterIncludeRemoved(): void
    {
        $repository = $this->createSUT();

        $removed = MaterialitySectorMother::random();
        $removed->remove();

        $repository->add(MaterialitySectorMother::random());
        $repository->add($removed);

        $this->assertCount(1, $repository->findByFilter(new MaterialitySectorFilter()));
        $this->assertCount(2, $repository->findByFilter(new MaterialitySectorFilter(includeRemoved: true)));
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $materialitySector = MaterialitySectorMother::registeredMaterialitySector();
        $materialitySector->translate('translated-name', Locale::English);

        $repository->add($materialitySector);
        $repository->add(MaterialitySectorMother::random());

        $recovered = $repository->findByFilter(new MaterialitySectorFilter(name: 'translated-name'));

        $this->assertCount(1, $recovered);
        $this->assertEquals($materialitySector->id(), $recovered[0]->id());
    }

    public function testDefaultOrderIsByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialitySectorMother::fromName('C'));
        $repository->add(MaterialitySectorMother::fromName('A'));
        $repository->add(MaterialitySectorMother::fromName('B'));

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter(new MaterialitySectorFilter())));

        $filter = new MaterialitySectorFilter();
        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testDefaultOrderUsesTranslatedName(): void
    {
        $repository = $this->createSUT(Locale::English);

        $hotels = MaterialitySectorMother::fromName('Hoteles');
        $hotels->translate('Accommodation', Locale::English);
        $events = MaterialitySectorMother::fromName('Eventos');
        $events->translate('Events', Locale::English);

        $repository->add($events);
        $repository->add($hotels);

        $this->assertSame(['Accommodation', 'Events'], $this->names($repository->findByFilter(new MaterialitySectorFilter())));
    }

    public function testOrderByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new MaterialitySectorFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testOrderByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add(new MaterialitySector(id: MaterialitySectorId::generate(), name: 'C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new MaterialitySectorFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindAllExcludesRemovedAndSortsByName(): void
    {
        $repository = $this->createSUT();

        $removed = MaterialitySectorMother::fromName('A');
        $removed->remove();

        $repository->add(MaterialitySectorMother::fromName('C'));
        $repository->add(MaterialitySectorMother::fromName('B'));
        $repository->add($removed);

        foreach (range(1, 11) as $index) {
            $repository->add(MaterialitySectorMother::fromName('D' . str_pad((string) $index, 2, '0', STR_PAD_LEFT)));
        }

        $result = $repository->findAll();

        $this->assertCount(13, $result);
        $this->assertSame('B', $result[0]->name());
        $this->assertSame('C', $result[1]->name());
    }

    private function pagedFilter(int $page, int $limit): MaterialitySectorFilter
    {
        $filter = new MaterialitySectorFilter(limit: $limit);
        $filter->page = $page;

        return $filter;
    }

    /**
     * @param array<MaterialitySector> $materialitySectors
     *
     * @return list<string>
     */
    private function names(array $materialitySectors): array
    {
        return array_values(array_map(static fn(MaterialitySector $materialitySector) => $materialitySector->name(), $materialitySectors));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MaterialitySectorRepository;
}
