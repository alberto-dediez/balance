<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\Update;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Application\ElectricityMix\Update\EmissionFactorData;
use App\CarbonFootprint\Application\ElectricityMix\Update\UpdateElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Update\UpdateElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateElectricityMixServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', 'EM-1');

        $repository->add($electricityMix);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new UpdateElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
            countryId: new CountryId('C-FR'),
        );

        $result = $sut($request, $user);

        $electricityMixPersisted = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMixPersisted);
        $this->assertEquals('C-FR', (string) $electricityMixPersisted->countryId());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMixUpdated);
        $this->assertTrue($electricityMixPersisted->id()->equals($lastEvent->electricityMixId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::random();

        $repository->add($electricityMix);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
            countryId: $electricityMix->countryId(),
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $result = $sut($request, $user);

        $electricityMixPersisted = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMixPersisted);
        $this->assertCount(1, $electricityMixPersisted->emissionFactors());

        $persistedFactor = $electricityMixPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::KWh, $persistedFactor->unit());
    }

    public function testUpdateSyncsWellToTankFactors(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::random();

        $repository->add($electricityMix);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
            countryId: $electricityMix->countryId(),
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    wellToTankCo2eq: '0.61',
                    wellToTankCo2: '0.6',
                    wellToTankCh4: '0.01',
                    wellToTankN2o: '0.01',
                    wellToTankSource: 'DEFRA',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $result = $sut($request, $user);

        $electricityMixPersisted = $repository->find($result->electricityMixId);

        $this->assertNotNull($electricityMixPersisted);
        $this->assertCount(1, $electricityMixPersisted->emissionFactors());

        $persistedFactor = $electricityMixPersisted->wellToTankFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('0.61', $persistedFactor->co2eq());
        $this->assertSame('DEFRA', $persistedFactor->source());
        $this->assertSame(Unit::KWh, $persistedFactor->unit());
    }

    public function testUpdateToCountryOfAnotherMixThrows(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', 'EM-1');
        $repository->add($electricityMix);
        $repository->add(ElectricityMixMother::fromCountryAndId('C-FR', 'EM-2'));

        $sut = $this->createSUT($repository);

        $this->expectException(ElectricityMixCountryAlreadyRegisteredException::class);

        $sut(
            new UpdateElectricityMixRequest(
                electricityMixId: $electricityMix->id(),
                countryId: new CountryId('C-FR'),
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testUpdateKeepingOwnCountryDoesNotThrow(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', 'EM-1');
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new UpdateElectricityMixRequest(
                electricityMixId: $electricityMix->id(),
                countryId: new CountryId('C-ES'),
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $persisted = $repository->find($result->electricityMixId);

        $this->assertNotNull($persisted);
        $this->assertEquals('C-ES', (string) $persisted->countryId());
    }

    private function createSUT(
        ElectricityMixRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateElectricityMixService {
        return new UpdateElectricityMixService(
            electricityMixRepository: $repository,
            authorizer: new ElectricityMixAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
