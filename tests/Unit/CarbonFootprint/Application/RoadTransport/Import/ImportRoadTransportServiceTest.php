<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\Import;

use App\CarbonFootprint\Application\RoadTransport\Import\ImportRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Import\ImportRoadTransportService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportRoadTransportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewRoadTransportByNameWhenNoneIsSelected(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportRoadTransportRequest(
            name: 'Camión diésel',
            unit: Unit::Kilogram->value,
            vehicle: Vehicle::Trucks->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $roadTransport = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransport);
        $this->assertEquals('Camión diésel', $roadTransport->name());
        $this->assertSame(Vehicle::Trucks, $roadTransport->vehicle());

        $emissionFactor = $roadTransport->emissionFactorFor(2024);

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->unit());
    }

    public function testUpsertsExistingRoadTransportByName(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $existing = RoadTransportMother::fromName('Camión diésel');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportRoadTransportRequest(
            name: 'Camión diésel',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->roadTransportId));

        $roadTransport = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransport);
        $this->assertCount(1, $repository->findByFilter(new RoadTransportFilter(limit: 0)));
        $this->assertEquals('2.0', $roadTransport->emissionFactorFor(2024)?->co2eq());
    }

    public function testExplicitRoadTransportOnlyUpdatesEmissionFactor(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $existing = RoadTransportMother::from(name: 'Camión diésel', id: (string) RoadTransportId::generate(), unit: Unit::Kilogram, vehicle: Vehicle::Trucks);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportRoadTransportRequest(
            roadTransport: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Piece->value,
            vehicle: Vehicle::PassengerCars->value,
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $roadTransport = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransport);
        $this->assertEquals('Camión diésel', $roadTransport->name());
        $this->assertEquals(Unit::Kilogram, $roadTransport->unit());
        $this->assertSame(Vehicle::Trucks, $roadTransport->vehicle());
        $this->assertEquals('3.0', $roadTransport->emissionFactorFor(2025)?->co2eq());
    }

    public function testThrowsWhenNeitherRoadTransportNorNameProvided(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportRoadTransportRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportRoadTransportRequest(name: 'Camión diésel'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitRoadTransportNotFound(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportRoadTransportRequest(
                roadTransport: (string) RoadTransportId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportRoadTransportRequest(name: 'Camión diésel', year: '2024')));
    }

    private function createSUT(RoadTransportRepository $repository): ImportRoadTransportService
    {
        return new ImportRoadTransportService(
            roadTransportRepository: $repository,
            authorizer: new RoadTransportAuthorizer(),
        );
    }
}
