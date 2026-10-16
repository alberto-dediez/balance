<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\Import;

use App\CarbonFootprint\Application\Transport\Import\ImportTransportRequest;
use App\CarbonFootprint\Application\Transport\Import\ImportTransportService;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportTransportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewTransportByNameWhenNoneIsSelected(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportTransportRequest(
            name: 'Camión diésel',
            unit: Unit::Kilogram->value,
            vehicle: Vehicle::Trucks->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $transport = $repository->find($result->transportId);

        $this->assertNotNull($transport);
        $this->assertEquals('Camión diésel', $transport->name());
        $this->assertSame(Vehicle::Trucks, $transport->vehicle());

        $emissionFactor = $transport->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->unit());
    }

    public function testExplicitTransportOnlyUpdatesEmissionFactor(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);
        $existing = TransportMother::from(name: 'Camión diésel', id: (string) TransportId::generate(), unit: Unit::Kilogram, vehicle: Vehicle::Trucks);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportTransportRequest(
            transport: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Piece->value,
            vehicle: Vehicle::PassengerCars->value,
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $transport = $repository->find($result->transportId);

        $this->assertNotNull($transport);
        $this->assertEquals('Camión diésel', $transport->name());
        $this->assertEquals(Unit::Kilogram, $transport->unit());
        $this->assertSame(Vehicle::Trucks, $transport->vehicle());
        $this->assertEquals('3.0', $transport->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->co2eq());
    }

    public function testThrowsWhenNeitherTransportNorNameProvided(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportTransportRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportTransportRequest(name: 'Camión diésel'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitTransportNotFound(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportTransportRequest(
                transport: (string) TransportId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportTransportRequest(name: 'Camión diésel', year: '2024')));
    }

    private function createSUT(TransportRepository $repository): ImportTransportService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportTransportService(
            transportRepository: $repository,
            authorizer: new TransportAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
