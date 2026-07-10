<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CateringItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivityUpdated;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CateringItemActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateCateringItemActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = CateringItemActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = CateringItemMother::registeredCateringItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(), ActivityMother::emissionFactor());
        $entityRepo = CateringItemRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CateringItemEmissionFactorSelector($entityRepo);
        $sut = new UpdateCateringItemActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());

        $sut(new UpdateCateringItemActivityRequest(
            activityId: $activity->id(),
            cateringItemId: $entity->id(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram, 8.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(CateringItemActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = CateringItemActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new CateringItemEmissionFactorSelector(CateringItemRepositoryMother::inMemory());
        $sut = new UpdateCateringItemActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);

        $sut(new UpdateCateringItemActivityRequest(
            activityId: $activity->id(),
            cateringItemId: CateringItemId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new CateringItemEmissionFactorSelector(CateringItemRepositoryMother::inMemory());
        $sut = new UpdateCateringItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new UpdateCateringItemActivityRequest(
            activityId: \App\CarbonFootprint\Domain\Model\Activity\ActivityId::generate(),
            cateringItemId: CateringItemId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
