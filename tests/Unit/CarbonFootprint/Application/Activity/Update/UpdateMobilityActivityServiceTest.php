<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivityUpdated;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MobilityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMobilityActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = MobilityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = MobilityMother::registeredMobility();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer));
        $entityRepo = MobilityRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MobilityEmissionFactorSelector($entityRepo);
        $sut = new UpdateMobilityActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateMobilityActivityRequest(
            activityId: $activity->id(),
            mobilityId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 400.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(MobilityActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = MobilityActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new MobilityEmissionFactorSelector(MobilityRepositoryMother::inMemory());
        $sut = new UpdateMobilityActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateMobilityActivityRequest(
            activityId: $activity->id(),
            mobilityId: MobilityId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MobilityEmissionFactorSelector(MobilityRepositoryMother::inMemory());
        $sut = new UpdateMobilityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateMobilityActivityRequest(
            activityId: ActivityId::generate(),
            mobilityId: MobilityId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
