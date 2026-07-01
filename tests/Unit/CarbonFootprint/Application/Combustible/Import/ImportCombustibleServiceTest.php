<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\Import;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\Import\ImportCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Import\ImportCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportCombustibleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewCombustibleByNameWhenNoCombustibleIsSelected(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportCombustibleRequest(
            name: 'Diésel',
            unit: Unit::Kilogram->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $combustible = $repository->find($result->combustibleId);

        $this->assertNotNull($combustible);
        $this->assertEquals('Diésel', $combustible->name());

        $emissionFactor = $combustible->emissionFactorFor(2024, EmissionFactorGroupId::default());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->unit());
    }

    public function testUpsertsExistingCombustibleByName(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $existing = CombustibleMother::fromName('Diésel');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportCombustibleRequest(
            name: 'Diésel',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->combustibleId));

        $combustible = $repository->find($result->combustibleId);

        $this->assertNotNull($combustible);
        $this->assertCount(1, $repository->findByFilter(new CombustibleFilter(limit: 0)));
        $this->assertEquals('2.0', $combustible->emissionFactorFor(2024, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testExplicitCombustibleOnlyUpdatesEmissionFactor(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $existing = CombustibleMother::from(name: 'Diésel', id: (string) CombustibleId::generate(), unit: Unit::Kilogram);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportCombustibleRequest(
            combustible: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Piece->value,
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $combustible = $repository->find($result->combustibleId);

        $this->assertNotNull($combustible);
        $this->assertEquals('Diésel', $combustible->name());
        $this->assertEquals(Unit::Kilogram, $combustible->unit());
        $this->assertEquals('3.0', $combustible->emissionFactorFor(2025, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testThrowsWhenNeitherCombustibleNorNameProvided(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportCombustibleRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportCombustibleRequest(name: 'Diésel'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitCombustibleNotFound(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportCombustibleRequest(
                combustible: (string) CombustibleId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportCombustibleRequest(name: 'Diésel', year: '2024')));
    }

    private function createSUT(CombustibleRepository $repository): ImportCombustibleService
    {
        return new ImportCombustibleService(
            combustibleRepository: $repository,
            authorizer: new CombustibleAuthorizer(),
        );
    }
}
