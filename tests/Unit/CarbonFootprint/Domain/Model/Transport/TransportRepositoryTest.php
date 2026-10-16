<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportMode;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TransportRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddTransportToRepository(): void
    {
        $repository = $this->createSUT();

        $transport = TransportMother::registeredTransport();

        $this->assertNull($repository->find($transport->id()));

        $repository->add($transport);

        $recoveredTransport = $repository->find($transport->id());

        $this->assertNotNull($recoveredTransport);
        $this->assertEquals($transport->id(), $recoveredTransport->id());
    }

    public function testRemoveTransportToRepository(): void
    {
        $repository = $this->createSUT();

        $transport = TransportMother::registeredTransport();

        $repository->add($transport);

        $recoveredTransport = $repository->find($transport->id());

        $this->assertNotNull($recoveredTransport);
        $this->assertEquals($transport->id(), $recoveredTransport->id());

        $repository->remove($transport);

        $this->assertNull($repository->find($transport->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::fromName('Nombre específico'));

        $filter = new TransportFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new TransportFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithRailModeReturnsOnlyTrains(): void
    {
        $repository = $this->createSUT();

        $train = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $repository->add($train);
        $repository->add(TransportMother::from('Camión', (string) TransportId::generate(), vehicle: Vehicle::Trucks));

        $filter = new TransportFilter(mode: TransportMode::Rail);
        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals($train->id(), $result[0]->id());
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithRoadModeExcludesTrains(): void
    {
        $repository = $this->createSUT();

        $truck = TransportMother::from('Camión', (string) TransportId::generate(), vehicle: Vehicle::Trucks);
        $repository->add(TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate()));
        $repository->add($truck);

        $filter = new TransportFilter(mode: TransportMode::Road);
        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals($truck->id(), $result[0]->id());
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::random());
        $repository->add(TransportMother::fromName('Nombre específico'));

        $filter = new TransportFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $transport = TransportMother::registeredTransport();

        $repository->add($transport);

        $recoveredTransport = $repository->find($transport->id());

        $this->assertNotNull($recoveredTransport);

        $recoveredTransport->remove();

        $repository->add($transport);

        $this->assertNull($repository->find($transport->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $transport = TransportMother::registeredTransport();

        $this->assertNull($repository->find($transport->id()));

        $transport->translate('translated-name', Locale::English);

        $repository->add($transport);

        $recoveredTransport = $repository->find($transport->id());

        $this->assertNotNull($recoveredTransport);
        $this->assertEquals($transport->id(), $recoveredTransport->id());
        $this->assertEquals('translated-name', $recoveredTransport->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $transport = TransportMother::registeredTransport();

        $transport->translate('translated-name', Locale::English);

        $repository->add($transport);

        $recoveredTransports = $repository->findByFilter(new TransportFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredTransports);
        $this->assertEquals($transport->id(), $recoveredTransports[0]->id());
        $this->assertEquals('translated-name', $recoveredTransports[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): TransportRepository;
}
