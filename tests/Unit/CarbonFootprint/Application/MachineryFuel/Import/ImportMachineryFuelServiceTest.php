<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\Import;

use App\CarbonFootprint\Application\MachineryFuel\Import\ImportMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Import\ImportMachineryFuelService;
use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportMachineryFuelServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewMachineryFuelByNameWhenNoMachineryFuelIsSelected(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportMachineryFuelRequest(
            name: 'Tractor diesel',
            unit: Unit::Liter->value,
            machineryType: 'agricultural',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $machineryFuel = $repository->find($result->machineryFuelId);

        $this->assertNotNull($machineryFuel);
        $this->assertEquals('Tractor diesel', $machineryFuel->name());

        $emissionFactor = $machineryFuel->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Liter, $emissionFactor->unit());
    }

    public function testImportsWellToTankFactor(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportMachineryFuelRequest(
            name: 'Tractor diesel',
            unit: Unit::Liter->value,
            machineryType: 'agricultural',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            wellToTankCo2eq: '0,61',
            wellToTankCo2: '0,6',
            wellToTankCh4: '0,01',
            wellToTankN2o: '0,01',
            wellToTankSource: 'DEFRA',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $machineryFuel = $repository->find($result->machineryFuelId);

        $this->assertNotNull($machineryFuel);
        $this->assertEquals('Tractor diesel', $machineryFuel->name());

        $wellToTankFactor = $machineryFuel->wellToTankFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('0.6', $wellToTankFactor->co2());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
    }

    public function testImportsZeroWellToTankWhenColumnsAreMissing(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportMachineryFuelRequest(
            name: 'Tractor diesel',
            unit: Unit::Liter->value,
            machineryType: 'agricultural',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $machineryFuel = $repository->find($result->machineryFuelId);

        $this->assertNotNull($machineryFuel);
        $this->assertEquals('Tractor diesel', $machineryFuel->name());

        $wellToTankFactor = $machineryFuel->wellToTankFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0', $wellToTankFactor->co2eq());
        $this->assertSame('0', $wellToTankFactor->ch4());
        $this->assertNull($wellToTankFactor->source());
    }

    public function testExplicitMachineryFuelOnlyUpdatesEmissionFactor(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);
        $existing = MachineryFuelMother::from(name: 'Tractor diesel', id: (string) MachineryFuelId::generate(), unit: Unit::Liter);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportMachineryFuelRequest(
            machineryFuel: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Kilogram->value,
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $machineryFuel = $repository->find($result->machineryFuelId);

        $this->assertNotNull($machineryFuel);
        $this->assertEquals('Tractor diesel', $machineryFuel->name());
        $this->assertEquals(Unit::Liter, $machineryFuel->unit());
        $this->assertEquals('3.0', $machineryFuel->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->co2eq());
    }

    public function testThrowsWhenNeitherMachineryFuelNorNameProvided(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMachineryFuelRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMachineryFuelRequest(name: 'Tractor diesel'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitMachineryFuelNotFound(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportMachineryFuelRequest(
                machineryFuel: (string) MachineryFuelId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportMachineryFuelRequest(name: 'Tractor diesel', year: '2024')));
    }

    private function createSUT(MachineryFuelRepository $repository): ImportMachineryFuelService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportMachineryFuelService(
            machineryFuelRepository: $repository,
            authorizer: new MachineryFuelAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
