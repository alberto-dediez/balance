<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\Update;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\Update\EmissionFactorData;
use App\CarbonFootprint\Application\ElectricityMarketer\Update\UpdateElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Update\UpdateElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateElectricityMarketerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();

        $repository->add($electricityMarketer);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new UpdateElectricityMarketerRequest(
            electricityMarketerId: $electricityMarketer->id(),
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $electricityMarketerPersisted = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketerPersisted);
        $this->assertEquals('changedName', $electricityMarketerPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMarketerUpdated);
        $this->assertTrue($electricityMarketerPersisted->id()->equals($lastEvent->electricityMarketerId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();

        $repository->add($electricityMarketer);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new UpdateElectricityMarketerRequest(
            electricityMarketerId: $electricityMarketer->id(),
            name: 'changedName',
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $result = $sut($request, $user);

        $electricityMarketerPersisted = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketerPersisted);
        $this->assertCount(1, $electricityMarketerPersisted->emissionFactors());

        $persistedFactor = $electricityMarketerPersisted->emissionFactorFor(2024, EmissionFactorGroupId::default());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::KWh, $persistedFactor->unit());
    }

    private function createSUT(ElectricityMarketerRepository $repository): UpdateElectricityMarketerService
    {
        return new UpdateElectricityMarketerService(
            electricityMarketerRepository: $repository,
            authorizer: new ElectricityMarketerAuthorizer(),
        );
    }
}
