<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste\Import;

use App\CarbonFootprint\Application\Waste\Import\ImportWasteRequest;
use App\CarbonFootprint\Application\Waste\Import\ImportWasteService;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteFilter;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportWasteServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewWasteByNameWhenNoWasteIsSelected(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportWasteRequest(
            name: 'Cartón',
            unit: Unit::Kilogram->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $waste = $repository->find($result->wasteId);

        $this->assertNotNull($waste);
        $this->assertEquals('Cartón', $waste->name());

        $emissionFactor = $waste->emissionFactorFor(2024, EmissionFactorGroupId::default());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->unit());
    }

    public function testUpsertsExistingWasteByName(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $existing = WasteMother::fromName('Cartón');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportWasteRequest(
            name: 'Cartón',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->wasteId));

        $waste = $repository->find($result->wasteId);

        $this->assertNotNull($waste);
        $this->assertCount(1, $repository->findByFilter(new WasteFilter(limit: 0)));
        $this->assertEquals('2.0', $waste->emissionFactorFor(2024, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testExplicitWasteOnlyUpdatesEmissionFactor(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $existing = WasteMother::from(name: 'Cartón', id: (string) WasteId::generate(), unit: Unit::Kilogram);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportWasteRequest(
            waste: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Piece->value,
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $waste = $repository->find($result->wasteId);

        $this->assertNotNull($waste);
        $this->assertEquals('Cartón', $waste->name());
        $this->assertEquals(Unit::Kilogram, $waste->unit());
        $this->assertEquals('3.0', $waste->emissionFactorFor(2025, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testThrowsWhenNeitherWasteNorNameProvided(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportWasteRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportWasteRequest(name: 'Cartón'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitWasteNotFound(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportWasteRequest(
                waste: (string) WasteId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportWasteRequest(name: 'Cartón', year: '2024')));
    }

    private function createSUT(WasteRepository $repository): ImportWasteService
    {
        return new ImportWasteService(
            wasteRepository: $repository,
            authorizer: new WasteAuthorizer(),
        );
    }
}
