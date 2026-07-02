<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\Update;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Application\RoadTransport\Update\EmissionFactorData;
use App\CarbonFootprint\Application\RoadTransport\Update\UpdateRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Update\UpdateRoadTransportService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportTranslated;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportUpdated;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateRoadTransportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();

        $repository->add($roadTransport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::Trucks,
        );

        $result = $sut($request, $user);

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransportPersisted);
        $this->assertSame(Unit::Piece, $roadTransportPersisted->unit());
        $this->assertSame(Vehicle::Trucks, $roadTransportPersisted->vehicle());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoadTransportUpdated);
        $this->assertTrue($roadTransportPersisted->id()->equals($lastEvent->roadTransportId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);

        $roadTransport = RoadTransportMother::random();

        $repository->add($roadTransport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
            name: 'changedName',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        );

        $result = $sut($request, $user);

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransportPersisted);
        $this->assertEquals('changedName', $roadTransportPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoadTransportTranslated);
        $this->assertTrue($roadTransportPersisted->id()->equals($lastEvent->roadTransportId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $roadTransport = RoadTransportMother::random();

        $repository->add($roadTransport);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::PassengerCars,
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

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransportPersisted);
        $this->assertCount(1, $roadTransportPersisted->emissionFactors());

        $persistedFactor = $roadTransportPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        // co2eq is respected as provided by the user (non-zero), not recomputed from co2/ch4/n2o.
        $this->assertSame('1.5', $persistedFactor->co2eq());
        // The emission factor unit is derived from the road transport's unit, not user input.
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(
        RoadTransportRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateRoadTransportService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateRoadTransportService(
            roadTransportRepository: $repository,
            authorizer: new RoadTransportAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
