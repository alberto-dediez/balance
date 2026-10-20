<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\MachineryFuelEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivityUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateMachineryFuelActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = MachineryFuelActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = MachineryFuelMother::registeredMachineryFuel();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter));
        $entityRepo = MachineryFuelRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new MachineryFuelEmissionFactorSelector($entityRepo);
        $sut = new UpdateMachineryFuelActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateMachineryFuelActivityRequest(
            activityId: $activity->id(),
            machineryFuelId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 80.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(MachineryFuelActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = MachineryFuelActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new MachineryFuelEmissionFactorSelector(MachineryFuelRepositoryMother::inMemory());
        $sut = new UpdateMachineryFuelActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateMachineryFuelActivityRequest(
            activityId: $activity->id(),
            machineryFuelId: MachineryFuelId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new MachineryFuelEmissionFactorSelector(MachineryFuelRepositoryMother::inMemory());
        $sut = new UpdateMachineryFuelActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateMachineryFuelActivityRequest(
            activityId: ActivityId::generate(),
            machineryFuelId: MachineryFuelId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }

    public function testStoresWellToTankEmissions(): void
    {
        $activity = $this->updateWith(ActivityMother::wellToTankEmissionFactor()->withUnit(Unit::Liter));

        $this->assertSame('0.61', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('610', $activity->wellToTankEmissions()->co2eq());
    }

    public function testStoresZeroWellToTankEmissionsWhenMasterHasNoWellToTank(): void
    {
        $activity = $this->updateWith(null);

        $this->assertSame('0', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('0', $activity->wellToTankEmissions()->co2eq());
    }

    private function updateWith(?EmissionFactor $wellToTankFactor): MachineryFuelActivity
    {
        $activity = MachineryFuelActivityMother::registeredWithWellToTank();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $entity = MachineryFuelMother::registeredMachineryFuel();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter), $wellToTankFactor);
        $entityRepo = MachineryFuelRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new UpdateMachineryFuelActivityService($repository, new ActivityAuthorizer(), new MachineryFuelEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $sut(new UpdateMachineryFuelActivityRequest(
            activityId: $activity->id(),
            machineryFuelId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $updated = $repository->findById($activity->id());
        $this->assertInstanceOf(MachineryFuelActivity::class, $updated);

        return $updated;
    }
}
