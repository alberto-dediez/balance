<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivityUpdated;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\OvernightStayActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateOvernightStayActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = OvernightStayActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = OvernightStayMother::registeredOvernightStay();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion));
        $entityRepo = OvernightStayRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new OvernightStayEmissionFactorSelector($entityRepo);
        $sut = new UpdateOvernightStayActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateOvernightStayActivityRequest(
            activityId: $activity->id(),
            overnightStayId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion, 4.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(OvernightStayActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertNull($activity->nights());
        $this->assertNull($activity->people());
    }

    public function testUseCaseWithNightsAndPeople(): void
    {
        $activity = OvernightStayActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = OvernightStayMother::registeredOvernightStay();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion));
        $entityRepo = OvernightStayRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new OvernightStayEmissionFactorSelector($entityRepo);
        $sut = new UpdateOvernightStayActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateOvernightStayActivityRequest(
            activityId: $activity->id(),
            overnightStayId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion, 8.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            nights: 4,
            people: 2,
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertSame(4, $activity->nights());
        $this->assertSame(2, $activity->people());

        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(OvernightStayActivityUpdated::class, $event);
        $this->assertSame(4, $event->nights());
        $this->assertSame(2, $event->people());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = OvernightStayActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new OvernightStayEmissionFactorSelector(OvernightStayRepositoryMother::inMemory());
        $sut = new UpdateOvernightStayActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateOvernightStayActivityRequest(
            activityId: $activity->id(),
            overnightStayId: OvernightStayId::generate(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new OvernightStayEmissionFactorSelector(OvernightStayRepositoryMother::inMemory());
        $sut = new UpdateOvernightStayActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateOvernightStayActivityRequest(
            activityId: ActivityId::generate(),
            overnightStayId: OvernightStayId::generate(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
