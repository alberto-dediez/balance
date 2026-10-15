<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\Import;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\Import\ImportCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Import\ImportCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportCapitalGoodServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewCapitalGoodByNameWithDefaults(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportCapitalGoodRequest(
            name: 'Consultoría',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $capitalGood = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGood);
        $this->assertEquals('Consultoría', $capitalGood->name());
        $this->assertEquals(CapitalGoodCategory::Others, $capitalGood->category());
        $this->assertEquals(Unit::Euro, $capitalGood->unit());

        $emissionFactor = $capitalGood->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->emissionFactor()->co2eq());
        $this->assertEquals(Unit::Euro, $emissionFactor->emissionFactor()->unit());
    }

    public function testCreatesNewCapitalGoodWithMappedCategoryAndUnit(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportCapitalGoodRequest(
                name: 'Publicidad',
                category: CapitalGoodCategory::FurnitureAndEquipment->value,
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

        $capitalGood = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGood);
        $this->assertEquals(CapitalGoodCategory::FurnitureAndEquipment, $capitalGood->category());
        $this->assertEquals(Unit::UsDollar, $capitalGood->unit());
    }

    public function testExplicitCapitalGoodOnlyUpdatesEmissionFactor(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);
        $existing = CapitalGoodMother::from(name: 'Consultoría', id: (string) CapitalGoodId::generate(), category: CapitalGoodCategory::Renovations);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportCapitalGoodRequest(
            capitalGood: (string) $existing->id(),
            name: 'Otro nombre',
            category: CapitalGoodCategory::VehiclesAndMachinery->value,
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $capitalGood = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGood);
        $this->assertEquals('Consultoría', $capitalGood->name());
        $this->assertEquals(CapitalGoodCategory::Renovations, $capitalGood->category());
        $this->assertEquals('3.0', $capitalGood->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testThrowsWhenNeitherCapitalGoodNorNameProvided(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportCapitalGoodRequest(year: '2024', emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId()), UserMother::adminUser());
    }

    public function testThrowsWhenEmissionFactorGroupIsMissing(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportCapitalGoodRequest(name: 'Consultoría', year: '2024', co2eq: '1.5'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportCapitalGoodRequest(name: 'Consultoría'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitCapitalGoodNotFound(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportCapitalGoodRequest(
                capitalGood: (string) CapitalGoodId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory(Locale::Spanish));

        $this->assertTrue($sut->isHandled(new ImportCapitalGoodRequest(name: 'Consultoría', year: '2024')));
    }

    private function createSUT(CapitalGoodRepository $repository): ImportCapitalGoodService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportCapitalGoodService(
            capitalGoodRepository: $repository,
            authorizer: new CapitalGoodAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
