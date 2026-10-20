<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\Import;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Application\GdOFactor\Import\ImportGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Import\ImportGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImportGdOFactorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCreatesNewGdOFactorByNameWhenNoneIsSelected(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportGdOFactorRequest(
            name: 'Factor GdO Solar',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $gdOFactor = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactor);
        $this->assertEquals('Factor GdO Solar', $gdOFactor->name());

        $emissionFactor = $gdOFactor->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::KWh, $emissionFactor->unit());
    }

    public function testImportsWellToTankFactor(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportGdOFactorRequest(
            name: 'Factor GdO Solar',
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

        $gdOFactor = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactor);
        $this->assertEquals('Factor GdO Solar', $gdOFactor->name());

        $wellToTankFactor = $gdOFactor->wellToTankFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0.61', $wellToTankFactor->co2eq());
        $this->assertSame('0.6', $wellToTankFactor->co2());
        $this->assertSame('DEFRA', $wellToTankFactor->source());
    }

    public function testImportsZeroWellToTankWhenColumnsAreMissing(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportGdOFactorRequest(
            name: 'Factor GdO Solar',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $gdOFactor = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactor);
        $this->assertEquals('Factor GdO Solar', $gdOFactor->name());

        $wellToTankFactor = $gdOFactor->wellToTankFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($wellToTankFactor);
        $this->assertSame('0', $wellToTankFactor->co2eq());
        $this->assertSame('0', $wellToTankFactor->ch4());
        $this->assertNull($wellToTankFactor->source());
    }

    public function testExplicitGdOFactorOnlyUpdatesEmissionFactor(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $existing = GdOFactorMother::from(name: 'Factor GdO Solar', id: (string) GdOFactorId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportGdOFactorRequest(
            gdOFactor: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $gdOFactor = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactor);
        $this->assertEquals('Factor GdO Solar', $gdOFactor->name());
        $this->assertEquals('3.0', $gdOFactor->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->co2eq());
    }

    public function testThrowsWhenNeitherGdOFactorNorNameProvided(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportGdOFactorRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportGdOFactorRequest(name: 'Factor GdO Solar'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitGdOFactorNotFound(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportGdOFactorRequest(
                gdOFactor: (string) GdOFactorId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportGdOFactorRequest(name: 'Factor GdO Solar', year: '2024')));
    }

    private function createSUT(GdOFactorRepository $repository): ImportGdOFactorService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportGdOFactorService(
            gdOFactorRepository: $repository,
            authorizer: new GdOFactorAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
