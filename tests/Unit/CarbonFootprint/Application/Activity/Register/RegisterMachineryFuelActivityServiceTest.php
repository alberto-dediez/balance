<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MachineryFuelEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityService;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivityRegistered;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterMachineryFuelActivityServiceTest extends TestCase
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

        $entity = MachineryFuelMother::registeredMachineryFuel();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter));
        $entityRepo = MachineryFuelRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MachineryFuelEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterMachineryFuelActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterMachineryFuelActivityRequest(
            accountId: new AccountId('account-id'),
            machineryFuelId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 50.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(MachineryFuelActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(MachineryFuelActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new MachineryFuelEmissionFactorSelector(MachineryFuelRepositoryMother::inMemory());
        $sut = new RegisterMachineryFuelActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterMachineryFuelActivityRequest(
            accountId: new AccountId('account-id'),
            machineryFuelId: MachineryFuelId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MachineryFuelEmissionFactorSelector(MachineryFuelRepositoryMother::inMemory());
        $sut = new RegisterMachineryFuelActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterMachineryFuelActivityRequest(
            accountId: new AccountId('account-id'),
            machineryFuelId: MachineryFuelId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
