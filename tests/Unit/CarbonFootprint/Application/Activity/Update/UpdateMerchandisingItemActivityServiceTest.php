<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MerchandisingItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivityUpdated;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMerchandisingItemActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = MerchandisingItemActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = MerchandisingItemMother::registeredMerchandisingItem();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece), ActivityMother::emissionFactor()->withUnit(Unit::Piece));
        $entityRepo = MerchandisingItemRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MerchandisingItemEmissionFactorSelector($entityRepo);
        $sut = new UpdateMerchandisingItemActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());

        $sut(new UpdateMerchandisingItemActivityRequest(
            activityId: $activity->id(),
            merchandisingItemId: $entity->id(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Piece, 50.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(MerchandisingItemActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = MerchandisingItemActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new MerchandisingItemEmissionFactorSelector(MerchandisingItemRepositoryMother::inMemory());
        $sut = new UpdateMerchandisingItemActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);

        $sut(new UpdateMerchandisingItemActivityRequest(
            activityId: $activity->id(),
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Piece),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MerchandisingItemEmissionFactorSelector(MerchandisingItemRepositoryMother::inMemory());
        $sut = new UpdateMerchandisingItemActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new UpdateMerchandisingItemActivityRequest(
            activityId: \App\CarbonFootprint\Domain\Model\Activity\ActivityId::generate(),
            merchandisingItemId: MerchandisingItemId::generate(),
            condition: ItemCondition::New,
            quantity: ActivityMother::quantity(Unit::Piece),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
