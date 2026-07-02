<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityService;
use App\CarbonFootprint\Application\Activity\WasteEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivityRegistered;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterWasteActivityServiceTest extends TestCase
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

        $entity = WasteMother::registeredWaste();
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = WasteRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new WasteEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterWasteActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterWasteActivityRequest(
            wasteId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 75.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(WasteActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(WasteActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new WasteEmissionFactorSelector(WasteRepositoryMother::inMemory());
        $sut = new RegisterWasteActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterWasteActivityRequest(
            wasteId: WasteId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new WasteEmissionFactorSelector(WasteRepositoryMother::inMemory());
        $sut = new RegisterWasteActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterWasteActivityRequest(
            wasteId: WasteId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
