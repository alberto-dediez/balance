<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\Import;

use App\CarbonFootprint\Application\Mobility\Import\ImportMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Import\ImportMobilityService;
use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportMobilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewMobilityByNameWhenNoMobilityIsSelected(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportMobilityRequest(
            name: 'Vuelo',
            unit: Unit::Kilometer->value,
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $mobility = $repository->find($result->mobilityId);

        $this->assertNotNull($mobility);
        $this->assertEquals('Vuelo', $mobility->name());

        $emissionFactor = $mobility->emissionFactorFor(2024);

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilometer, $emissionFactor->unit());
    }

    public function testUpsertsExistingMobilityByName(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $existing = MobilityMother::fromName('Vuelo');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportMobilityRequest(
            name: 'Vuelo',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->mobilityId));

        $mobility = $repository->find($result->mobilityId);

        $this->assertNotNull($mobility);
        $this->assertCount(1, $repository->findByFilter(new MobilityFilter(limit: 0)));
        $this->assertEquals('2.0', $mobility->emissionFactorFor(2024)?->co2eq());
    }

    public function testExplicitMobilityOnlyUpdatesEmissionFactor(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $existing = MobilityMother::from(name: 'Vuelo', id: (string) MobilityId::generate(), unit: Unit::Kilometer);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportMobilityRequest(
            mobility: (string) $existing->id(),
            name: 'Otro nombre',
            unit: Unit::Piece->value,
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $mobility = $repository->find($result->mobilityId);

        $this->assertNotNull($mobility);
        $this->assertEquals('Vuelo', $mobility->name());
        $this->assertEquals(Unit::Kilometer, $mobility->unit());
        $this->assertEquals('3.0', $mobility->emissionFactorFor(2025)?->co2eq());
    }

    public function testThrowsWhenNeitherMobilityNorNameProvided(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMobilityRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportMobilityRequest(name: 'Vuelo'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitMobilityNotFound(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportMobilityRequest(
                mobility: (string) MobilityId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportMobilityRequest(name: 'Vuelo', year: '2024')));
    }

    private function createSUT(MobilityRepository $repository): ImportMobilityService
    {
        return new ImportMobilityService(
            mobilityRepository: $repository,
            authorizer: new MobilityAuthorizer(),
        );
    }
}
