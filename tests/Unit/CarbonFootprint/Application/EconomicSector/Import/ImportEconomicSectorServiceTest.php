<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\Import;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\Import\ImportEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Import\ImportEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportEconomicSectorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewEconomicSectorByNameWithDefaults(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportEconomicSectorRequest(
            name: 'Fabricación de productos metálicos',
            classificationCode: '25',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $economicSector = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSector);
        $this->assertEquals('Fabricación de productos metálicos', $economicSector->name());
        $this->assertEquals(Unit::Euro, $economicSector->unit());
        $this->assertSame('25', $economicSector->classificationCode());

        $emissionFactor = $economicSector->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->emissionFactor()->co2eq());
        $this->assertEquals(Unit::Euro, $emissionFactor->emissionFactor()->unit());
    }

    public function testCreatesNewEconomicSectorWithMappedUnit(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportEconomicSectorRequest(
                name: 'Fabricación de maquinaria',
                unit: Unit::UsDollar->value,
                year: '2024',
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.3',
                n2o: '0.2',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $economicSector = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSector);
        $this->assertEquals(Unit::UsDollar, $economicSector->unit());
    }

    public function testExplicitEconomicSectorOnlyUpdatesEmissionFactor(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);
        $existing = EconomicSectorMother::from(name: 'Fabricación de productos metálicos', id: (string) EconomicSectorId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportEconomicSectorRequest(
            economicSector: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $economicSector = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSector);
        $this->assertEquals('Fabricación de productos metálicos', $economicSector->name());
        $this->assertEquals('3.0', $economicSector->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testLocatesEconomicSectorByClassificationCode(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);
        $existing = EconomicSectorMother::from(name: 'Fabricación de productos metálicos', id: (string) EconomicSectorId::generate(), classificationCode: '25');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportEconomicSectorRequest(
                name: 'Otro nombre',
                classificationCode: ' 25 ',
                year: '2025',
                co2eq: '0.35',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $this->assertTrue($existing->id()->equals($result->economicSectorId));
        $this->assertSame(1, $repository->totalResultsByFilter(new EconomicSectorFilter()));

        $economicSector = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSector);
        $this->assertSame('Fabricación de productos metálicos', $economicSector->name());
        $this->assertSame('0.35', $economicSector->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testThrowsWhenCreatingWithNonCurrencyUnit(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        try {
            $sut(
                new ImportEconomicSectorRequest(
                    name: 'Fabricación de productos metálicos',
                    unit: Unit::Kilogram->value,
                    year: '2024',
                    co2eq: '1.5',
                    emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
                ),
                UserMother::adminUser(),
            );
            $this->fail('\InvalidArgumentException expected');
        } catch (\InvalidArgumentException) {
            $this->assertSame(0, $repository->totalResultsByFilter(new EconomicSectorFilter()));
        }
    }

    public function testThrowsWhenNeitherEconomicSectorNorNameProvided(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportEconomicSectorRequest(year: '2024', emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId()), UserMother::adminUser());
    }

    public function testThrowsWhenEmissionFactorGroupIsMissing(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportEconomicSectorRequest(name: 'Fabricación de productos metálicos', year: '2024', co2eq: '1.5'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportEconomicSectorRequest(name: 'Fabricación de productos metálicos'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitEconomicSectorNotFound(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportEconomicSectorRequest(
                economicSector: (string) EconomicSectorId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory(Locale::Spanish));

        $this->assertTrue($sut->isHandled(new ImportEconomicSectorRequest(name: 'Fabricación de productos metálicos', year: '2024')));
    }

    private function createSUT(EconomicSectorRepository $repository): ImportEconomicSectorService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportEconomicSectorService(
            economicSectorRepository: $repository,
            authorizer: new EconomicSectorAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
