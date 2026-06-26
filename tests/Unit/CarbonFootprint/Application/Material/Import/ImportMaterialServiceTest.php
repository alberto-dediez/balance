<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\Import;

use App\CarbonFootprint\Application\Material\Import\ImportMaterialRequest;
use App\CarbonFootprint\Application\Material\Import\ImportMaterialService;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportMaterialServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewMaterialByNameWhenNoMaterialIsSelected(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportMaterialRequest(
            name: 'Acero',
            category: MaterialCategory::Metal->value,
            unit: Unit::Kilogram->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $material = $repository->find($result->materialId);

        $this->assertNotNull($material);
        $this->assertEquals('Acero', $material->name());
        $this->assertEquals(MaterialCategory::Metal, $material->category());

        $emissionFactor = $material->emissionFactorFor(2024);

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->newFactor()->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->newFactor()->unit());
    }

    public function testUpsertsExistingMaterialByName(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $existing = MaterialMother::fromName('Acero');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportMaterialRequest(
            name: 'Acero',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->materialId));

        $material = $repository->find($result->materialId);

        $this->assertNotNull($material);
        $this->assertCount(1, $repository->findByFilter(new MaterialFilter(limit: 0)));
        $this->assertEquals('2.0', $material->emissionFactorFor(2024)?->newFactor()->co2eq());
    }

    public function testExplicitMaterialOnlyUpdatesEmissionFactor(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $existing = MaterialMother::from(name: 'Acero', id: (string) MaterialId::generate(), category: MaterialCategory::Metal);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportMaterialRequest(
            material: (string) $existing->id(),
            name: 'Otro nombre',
            category: MaterialCategory::Glass->value,
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $material = $repository->find($result->materialId);

        $this->assertNotNull($material);
        $this->assertEquals('Acero', $material->name());
        $this->assertEquals(MaterialCategory::Metal, $material->category());
        $this->assertEquals('3.0', $material->emissionFactorFor(2025)?->newFactor()->co2eq());
    }

    public function testThrowsWhenNeitherMaterialNorNameProvided(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMaterialRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMaterialRequest(name: 'Acero'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitMaterialNotFound(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportMaterialRequest(
                material: (string) MaterialId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportMaterialRequest(name: 'Acero', year: '2024')));
    }

    private function createSUT(MaterialRepository $repository): ImportMaterialService
    {
        return new ImportMaterialService(
            materialRepository: $repository,
            authorizer: new MaterialAuthorizer(),
        );
    }
}
