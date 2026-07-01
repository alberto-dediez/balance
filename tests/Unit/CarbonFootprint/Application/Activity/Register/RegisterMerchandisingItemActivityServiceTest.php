<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MerchandisingItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivityRegistered;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterMerchandisingItemActivityServiceTest extends TestCase
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

        $entity = MerchandisingItemMother::registeredMerchandisingItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor(), ActivityMother::emissionFactor());
        $entityRepo = MerchandisingItemRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MerchandisingItemEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterMerchandisingItemActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterMerchandisingItemActivityRequest(
            merchandisingItemId: $entity->id(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Piece, 100.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(MerchandisingItemActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(MerchandisingItemActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new MerchandisingItemEmissionFactorSelector(MerchandisingItemRepositoryMother::inMemory());
        $sut = new RegisterMerchandisingItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterMerchandisingItemActivityRequest(
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Piece),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MerchandisingItemEmissionFactorSelector(MerchandisingItemRepositoryMother::inMemory());
        $sut = new RegisterMerchandisingItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterMerchandisingItemActivityRequest(
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::Recycled,
            quantity: ActivityMother::quantity(Unit::Piece),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        )));
    }
}
