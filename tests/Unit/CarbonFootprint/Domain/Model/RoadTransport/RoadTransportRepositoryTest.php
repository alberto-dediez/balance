<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RoadTransportRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddRoadTransportToRepository(): void
    {
        $repository = $this->createSUT();

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $this->assertNull($repository->find($roadTransport->id()));

        $repository->add($roadTransport);

        $recoveredRoadTransport = $repository->find($roadTransport->id());

        $this->assertNotNull($recoveredRoadTransport);
        $this->assertEquals($roadTransport->id(), $recoveredRoadTransport->id());
    }

    public function testRemoveRoadTransportToRepository(): void
    {
        $repository = $this->createSUT();

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $repository->add($roadTransport);

        $recoveredRoadTransport = $repository->find($roadTransport->id());

        $this->assertNotNull($recoveredRoadTransport);
        $this->assertEquals($roadTransport->id(), $recoveredRoadTransport->id());

        $repository->remove($roadTransport);

        $this->assertNull($repository->find($roadTransport->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::fromName('Nombre específico'));

        $filter = new RoadTransportFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new RoadTransportFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::fromName('Nombre específico'));

        $filter = new RoadTransportFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $repository->add($roadTransport);

        $recoveredRoadTransport = $repository->find($roadTransport->id());

        $this->assertNotNull($recoveredRoadTransport);

        $recoveredRoadTransport->remove();

        $repository->add($roadTransport);

        $this->assertNull($repository->find($roadTransport->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $this->assertNull($repository->find($roadTransport->id()));

        $roadTransport->translate('translated-name', Locale::English);

        $repository->add($roadTransport);

        $recoveredRoadTransport = $repository->find($roadTransport->id());

        $this->assertNotNull($recoveredRoadTransport);
        $this->assertEquals($roadTransport->id(), $recoveredRoadTransport->id());
        $this->assertEquals('translated-name', $recoveredRoadTransport->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $roadTransport->translate('translated-name', Locale::English);

        $repository->add($roadTransport);

        $recoveredRoadTransports = $repository->findByFilter(new RoadTransportFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredRoadTransports);
        $this->assertEquals($roadTransport->id(), $recoveredRoadTransports[0]->id());
        $this->assertEquals('translated-name', $recoveredRoadTransports[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): RoadTransportRepository;
}
