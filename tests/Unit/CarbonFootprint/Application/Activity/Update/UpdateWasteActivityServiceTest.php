<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityService;
use App\CarbonFootprint\Application\Activity\WasteEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivityUpdated;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\WasteActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateWasteActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = WasteActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = WasteMother::registeredWaste();
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = WasteRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new WasteEmissionFactorSelector($entityRepo);
        $sut = new UpdateWasteActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateWasteActivityRequest(
            activityId: $activity->id(),
            wasteId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 90.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(WasteActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUseCaseWithContainer(): void
    {
        $activity = WasteActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = WasteMother::from(name: 'waste-name', id: (string) WasteId::generate(), unit: Unit::Kilogram, density: 250.0);
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = WasteRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new WasteEmissionFactorSelector($entityRepo);
        $sut = new UpdateWasteActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateWasteActivityRequest(
            activityId: $activity->id(),
            wasteId: $entity->id(),
            quantity: new Quantity(250.0, Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            containerCapacity: new Quantity(2, Unit::CubicMeter),
            fillPercentage: 50.0,
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $updated = $repository->findById($activity->id());
        assert($updated instanceof WasteActivity);

        $this->assertNotNull($updated->containerCapacity());
        $this->assertSame(2.0, $updated->containerCapacity()->value());
        $this->assertSame(Unit::CubicMeter, $updated->containerCapacity()->unit());
        $this->assertSame(50.0, $updated->fillPercentage());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = WasteActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new WasteEmissionFactorSelector(WasteRepositoryMother::inMemory());
        $sut = new UpdateWasteActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateWasteActivityRequest(
            activityId: $activity->id(),
            wasteId: WasteId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new WasteEmissionFactorSelector(WasteRepositoryMother::inMemory());
        $sut = new UpdateWasteActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateWasteActivityRequest(
            activityId: ActivityId::generate(),
            wasteId: WasteId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
