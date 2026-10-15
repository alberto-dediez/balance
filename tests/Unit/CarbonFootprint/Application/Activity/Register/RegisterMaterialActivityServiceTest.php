<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivityRegistered;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterMaterialActivityServiceTest extends TestCase
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

        $entity = MaterialMother::registeredMaterial();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(), ActivityMother::emissionFactor());
        $entityRepo = MaterialRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MaterialEmissionFactorSelector($entityRepo);
        $calculator = EmissionsCalculatorMother::create();
        $sut = new RegisterMaterialActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterMaterialActivityRequest(
            accountId: new AccountId('account-id'),
            materialId: $entity->id(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram, 10.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(MaterialActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(MaterialActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new MaterialEmissionFactorSelector(MaterialRepositoryMother::inMemory());
        $sut = new RegisterMaterialActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);
        $sut(new RegisterMaterialActivityRequest(
            accountId: new AccountId('account-id'),
            materialId: MaterialId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MaterialEmissionFactorSelector(MaterialRepositoryMother::inMemory());
        $sut = new RegisterMaterialActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new RegisterMaterialActivityRequest(
            accountId: new AccountId('account-id'),
            materialId: MaterialId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
