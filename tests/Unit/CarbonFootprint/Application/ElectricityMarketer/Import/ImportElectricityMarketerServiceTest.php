<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\Import;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\Import\ImportElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Import\ImportElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImportElectricityMarketerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCreatesNewElectricityMarketerByNameWhenNoneIsSelected(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportElectricityMarketerRequest(
            name: 'Comercializadora Solar',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $electricityMarketer = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketer);
        $this->assertEquals('Comercializadora Solar', $electricityMarketer->name());

        $emissionFactor = $electricityMarketer->emissionFactorFor(2024, EmissionFactorGroupId::default());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::KWh, $emissionFactor->unit());
    }

    public function testUpsertsExistingElectricityMarketerByName(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $existing = ElectricityMarketerMother::fromName('Comercializadora Solar');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportElectricityMarketerRequest(
            name: 'Comercializadora Solar',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->electricityMarketerId));

        $electricityMarketer = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketer);
        $this->assertCount(1, $repository->findByFilter(new ElectricityMarketerFilter(limit: 0)));
        $this->assertEquals('2.0', $electricityMarketer->emissionFactorFor(2024, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testExplicitElectricityMarketerOnlyUpdatesEmissionFactor(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $existing = ElectricityMarketerMother::from(name: 'Comercializadora Solar', id: (string) ElectricityMarketerId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportElectricityMarketerRequest(
            electricityMarketer: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $electricityMarketer = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketer);
        $this->assertEquals('Comercializadora Solar', $electricityMarketer->name());
        $this->assertEquals('3.0', $electricityMarketer->emissionFactorFor(2025, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testThrowsWhenNeitherElectricityMarketerNorNameProvided(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportElectricityMarketerRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportElectricityMarketerRequest(name: 'Comercializadora Solar'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitElectricityMarketerNotFound(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportElectricityMarketerRequest(
                electricityMarketer: (string) ElectricityMarketerId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportElectricityMarketerRequest(name: 'Comercializadora Solar', year: '2024')));
    }

    private function createSUT(ElectricityMarketerRepository $repository): ImportElectricityMarketerService
    {
        return new ImportElectricityMarketerService(
            electricityMarketerRepository: $repository,
            authorizer: new ElectricityMarketerAuthorizer(),
        );
    }
}
