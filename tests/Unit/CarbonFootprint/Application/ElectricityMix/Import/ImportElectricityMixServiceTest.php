<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\Import;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\Import\ImportElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Import\ImportElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImportElectricityMixServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCreatesNewElectricityMixByCountryWhenNoneIsSelected(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportElectricityMixRequest(
            country: 'ESP',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $electricityMix = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMix);
        $this->assertEquals('C-ES', (string) $electricityMix->countryId());

        $emissionFactor = $electricityMix->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::KWh, $emissionFactor->unit());
    }

    public function testTwoRowsOfTheSameCountryReuseTheSameElectricityMix(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $first = $sut(
            new ImportElectricityMixRequest(
                country: 'ESP',
                year: '2024',
                co2eq: '1.5',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $second = $sut(
            new ImportElectricityMixRequest(
                country: 'ESP',
                year: '2025',
                co2eq: '1.2',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $this->assertTrue($first->electricityMixId->equals($second->electricityMixId));

        $electricityMix = $repository->find($second->electricityMixId);

        $this->assertNotNull($electricityMix);
        $this->assertCount(2, $electricityMix->emissionFactors());
    }

    public function testExplicitElectricityMixOnlyUpdatesEmissionFactor(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $existing = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportElectricityMixRequest(
            electricityMix: (string) $existing->id(),
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $electricityMix = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMix);
        $this->assertEquals('C-ES', (string) $electricityMix->countryId());
        $this->assertEquals('3.0', $electricityMix->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->co2eq());
    }

    public function testThrowsWhenCountryIsUnknown(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportElectricityMixRequest(country: 'XXX', year: '2024'),
            UserMother::adminUser(),
        );
    }

    public function testThrowsWhenNeitherElectricityMixNorCountryProvided(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportElectricityMixRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenNameIsMissingForANewCountry(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportElectricityMixRequest(country: 'ESP', year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportElectricityMixRequest(country: 'ESP'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitElectricityMixNotFound(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportElectricityMixRequest(
                electricityMix: (string) ElectricityMixId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportElectricityMixRequest(country: 'ESP', year: '2024')));
    }

    private function createSUT(ElectricityMixRepository $repository): ImportElectricityMixService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        $countryRepository = CountryRepositoryMother::inMemory();
        $countryRepository->add(CountryMother::spain());

        return new ImportElectricityMixService(
            electricityMixRepository: $repository,
            authorizer: new ElectricityMixAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
            countryRepository: $countryRepository,
        );
    }
}
