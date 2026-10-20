<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivityRegistered;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCombustibleActivityServiceTest extends TestCase
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

        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter));
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CombustibleEmissionFactorSelector($entityRepo);
        $calculator = EmissionsCalculatorMother::create();
        $sut = new RegisterCombustibleActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterCombustibleActivityRequest(
            accountId: new AccountId('account-id'),
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 200.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(CombustibleActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(CombustibleActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new RegisterCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);
        $sut(new RegisterCombustibleActivityRequest(
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new RegisterCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new RegisterCombustibleActivityRequest(
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }

    public function testStoresWellToTankEmissions(): void
    {
        $activity = $this->registerWith(ActivityMother::wellToTankEmissionFactor()->withUnit(Unit::Liter));

        $this->assertSame('0.61', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('610', $activity->wellToTankEmissions()->co2eq());
        $this->assertSame('1500', $activity->emissions()->co2eq());
    }

    public function testStoresZeroWellToTankEmissionsWhenMasterHasNoWellToTank(): void
    {
        $activity = $this->registerWith(null);

        $this->assertSame('0', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('0', $activity->wellToTankEmissions()->co2eq());
    }

    private function registerWith(?EmissionFactor $wellToTankFactor): CombustibleActivity
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter), $wellToTankFactor);
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new RegisterCombustibleActivityService($activityRepo, new ActivityAuthorizer(), new CombustibleEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $result = $sut(new RegisterCombustibleActivityRequest(
            accountId: new AccountId('account-id'),
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);
        $this->assertInstanceOf(CombustibleActivity::class, $activity);

        return $activity;
    }
}
