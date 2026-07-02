<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivityUpdated;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMaterialActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = MaterialActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = MaterialMother::registeredMaterial();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(), ActivityMother::emissionFactor());
        $entityRepo = MaterialRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MaterialEmissionFactorSelector($entityRepo);
        $sut = new UpdateMaterialActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());

        $sut(new UpdateMaterialActivityRequest(
            activityId: $activity->id(),
            materialId: $entity->id(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram, 20.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(MaterialActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = MaterialActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new MaterialEmissionFactorSelector(MaterialRepositoryMother::inMemory());
        $sut = new UpdateMaterialActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);

        $sut(new UpdateMaterialActivityRequest(
            activityId: $activity->id(),
            materialId: MaterialId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MaterialEmissionFactorSelector(MaterialRepositoryMother::inMemory());
        $sut = new UpdateMaterialActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new UpdateMaterialActivityRequest(
            activityId: \App\CarbonFootprint\Domain\Model\Activity\ActivityId::generate(),
            materialId: MaterialId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
