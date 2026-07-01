<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CateringItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCateringItemActivityServiceTest extends TestCase
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

        $entity = CateringItemMother::registeredCateringItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(), ActivityMother::emissionFactor());
        $entityRepo = CateringItemRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CateringItemEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterCateringItemActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterCateringItemActivityRequest(
            cateringItemId: $entity->id(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram, 5.0),
            year: 2024,
        ), UserMother::adminUser());

        $this->assertInstanceOf(CateringItemActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(CateringItemActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new CateringItemEmissionFactorSelector(CateringItemRepositoryMother::inMemory());
        $sut = new RegisterCateringItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterCateringItemActivityRequest(
            cateringItemId: CateringItemId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new CateringItemEmissionFactorSelector(CateringItemRepositoryMother::inMemory());
        $sut = new RegisterCateringItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterCateringItemActivityRequest(
            cateringItemId: CateringItemId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
        )));
    }
}
