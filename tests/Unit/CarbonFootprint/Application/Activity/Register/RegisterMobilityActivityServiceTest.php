<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivity;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivityRegistered;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterMobilityActivityServiceTest extends TestCase
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

        $entity = MobilityMother::registeredMobility();
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = MobilityRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MobilityEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterMobilityActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterMobilityActivityRequest(
            mobilityId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 300.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(MobilityActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(MobilityActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new MobilityEmissionFactorSelector(MobilityRepositoryMother::inMemory());
        $sut = new RegisterMobilityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterMobilityActivityRequest(
            mobilityId: MobilityId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MobilityEmissionFactorSelector(MobilityRepositoryMother::inMemory());
        $sut = new RegisterMobilityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterMobilityActivityRequest(
            mobilityId: MobilityId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        )));
    }
}
