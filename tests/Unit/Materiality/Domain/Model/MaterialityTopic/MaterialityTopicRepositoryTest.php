<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MaterialityTopicRepositoryTest extends KernelTestCase
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

        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();

        $this->assertNull($repository->find($materialityTopic->id()));

        $repository->add($materialityTopic);

        $recovered = $repository->find($materialityTopic->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($materialityTopic->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();

        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();

        $repository->add($materialityTopic);
        $repository->remove($materialityTopic);

        $this->assertNull($repository->find($materialityTopic->id()));
    }

    public function testFindDoesNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();

        $repository->add($materialityTopic);

        $materialityTopic->remove();

        $repository->add($materialityTopic);

        $this->assertNull($repository->find($materialityTopic->id()));
    }

    public function testFindByNumber(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialityTopicMother::standard(StandardScope3Category::BusinessTravel));
        $repository->add(MaterialityTopicMother::random());

        $recovered = $repository->findByNumber(6);

        $this->assertNotNull($recovered);
        $this->assertSame(6, $recovered->number());
        $this->assertNull($repository->findByNumber(7));
    }

    public function testFindRecoversTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();
        $materialityTopic->translate('translated-name', 'translated-description', Locale::English);

        $repository->add($materialityTopic);

        $recovered = $repository->find($materialityTopic->id());

        $this->assertNotNull($recovered);
        $this->assertSame('translated-name', $recovered->name());
        $this->assertSame('translated-description', $recovered->description());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialityTopicMother::from(name: 'Lavandería externa', id: (string) MaterialityTopicId::generate(), position: 3));
        $repository->add(MaterialityTopicMother::from(name: 'Consumo de combustible', id: (string) MaterialityTopicId::generate(), scope: MaterialityScope::Scope1, direction: null, position: 1));
        $repository->add(MaterialityTopicMother::from(name: 'Franquicias propias', id: (string) MaterialityTopicId::generate(), direction: MaterialityTopicDirection::Downstream, position: 2));
        $repository->add(MaterialityTopicMother::standard(StandardScope3Category::Investments));

        $this->assertCount(4, $repository->findByFilter(new MaterialityTopicFilter()));
        $this->assertCount(1, $repository->findByFilter(new MaterialityTopicFilter(name: 'Lavandería')));
        $this->assertCount(1, $repository->findByFilter(new MaterialityTopicFilter(scope: MaterialityScope::Scope1)));
        $this->assertCount(3, $repository->findByFilter(new MaterialityTopicFilter(scope: MaterialityScope::Scope3)));
        $this->assertCount(2, $repository->findByFilter(new MaterialityTopicFilter(direction: MaterialityTopicDirection::Downstream)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityTopicFilter(standard: true)));
        $this->assertCount(3, $repository->findByFilter(new MaterialityTopicFilter(standard: false)));
        $this->assertSame(4, $repository->totalResultsByFilter(new MaterialityTopicFilter(limit: 1)));
    }

    public function testFindByFilterWithActive(): void
    {
        $repository = $this->createSUT();

        $active = MaterialityTopicMother::random();
        $inactive = MaterialityTopicMother::random();
        $inactive->update(MaterialityScope::Scope3, MaterialityTopicDirection::Upstream, $inactive->name(), '', 0, false);

        $repository->add($active);
        $repository->add($inactive);

        $this->assertCount(1, $repository->findByFilter(new MaterialityTopicFilter(active: true)));
        $this->assertCount(1, $repository->findByFilter(new MaterialityTopicFilter(active: false)));
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $materialityTopic = MaterialityTopicMother::registeredMaterialityTopic();
        $materialityTopic->translate('translated-name', '', Locale::English);

        $repository->add($materialityTopic);
        $repository->add(MaterialityTopicMother::random());

        $recovered = $repository->findByFilter(new MaterialityTopicFilter(name: 'translated-name'));

        $this->assertCount(1, $recovered);
        $this->assertEquals($materialityTopic->id(), $recovered[0]->id());
    }

    public function testDefaultOrderIsByPosition(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialityTopicMother::from(name: 'C', id: (string) MaterialityTopicId::generate(), position: 3));
        $repository->add(MaterialityTopicMother::from(name: 'A', id: (string) MaterialityTopicId::generate(), position: 1));
        $repository->add(MaterialityTopicMother::from(name: 'B', id: (string) MaterialityTopicId::generate(), position: 2));

        $names = array_map(
            static fn($materialityTopic) => $materialityTopic->name(),
            $repository->findByFilter(new MaterialityTopicFilter()),
        );

        $this->assertSame(['A', 'B', 'C'], $names);
    }

    public function testFindActiveExcludesInactiveAndRemoved(): void
    {
        $repository = $this->createSUT();

        $active = MaterialityTopicMother::random();
        $inactive = MaterialityTopicMother::random();
        $inactive->update(MaterialityScope::Scope3, MaterialityTopicDirection::Upstream, $inactive->name(), '', 0, false);
        $removed = MaterialityTopicMother::random();
        $removed->remove();

        $repository->add($active);
        $repository->add($inactive);
        $repository->add($removed);

        $result = $repository->findActive();

        $this->assertCount(1, $result);
        $this->assertEquals($active->id(), $result[0]->id());
    }

    public function testOrderByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add(new MaterialityTopic(id: MaterialityTopicId::generate(), name: 'A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add(new MaterialityTopic(id: MaterialityTopicId::generate(), name: 'B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add(new MaterialityTopic(id: MaterialityTopicId::generate(), name: 'C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new MaterialityTopicFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $names = array_map(
            static fn($materialityTopic) => $materialityTopic->name(),
            $repository->findByFilter($filter),
        );

        $this->assertSame(['A', 'C', 'B'], $names);
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MaterialityTopicRepository;
}
