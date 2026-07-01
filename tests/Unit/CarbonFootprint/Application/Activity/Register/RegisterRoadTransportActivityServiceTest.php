<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityService;
use App\CarbonFootprint\Application\Activity\RoadTransportEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivityRegistered;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterRoadTransportActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $entity = RoadTransportMother::registeredRoadTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = RoadTransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new RoadTransportEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterRoadTransportActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterRoadTransportActivityRequest(
            roadTransportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 500.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(RoadTransportActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(RoadTransportActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new RoadTransportEmissionFactorSelector(RoadTransportRepositoryMother::inMemory());
        $sut = new RegisterRoadTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterRoadTransportActivityRequest(
            roadTransportId: RoadTransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new RoadTransportEmissionFactorSelector(RoadTransportRepositoryMother::inMemory());
        $sut = new RegisterRoadTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterRoadTransportActivityRequest(
            roadTransportId: RoadTransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        )));
    }
}
