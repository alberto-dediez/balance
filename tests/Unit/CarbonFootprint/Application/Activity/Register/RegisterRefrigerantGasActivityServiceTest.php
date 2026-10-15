<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityRegistered;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterRefrigerantGasActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private RefrigerantGas $refrigerantGas;

    private RefrigerantGasEmissionFactorSelector $selector;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();
        $this->refrigerantGas->setEmissionFactor(ActivityMother::emissionFactor());

        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add($this->refrigerantGas);

        $this->selector = new RefrigerantGasEmissionFactorSelector($repository);
    }

    public function testUseCase(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();
        $sut = new RegisterRefrigerantGasActivityService($activityRepo, new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $result = $sut(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 2.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(RefrigerantGasActivity::class, $activity);
        $this->assertSame(3.0, (float) $activity->emissions()->co2eq());
        $this->assertInstanceOf(RefrigerantGasActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testConvertsTonnesToKilograms(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();
        $sut = new RegisterRefrigerantGasActivityService($activityRepo, new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $result = $sut(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Tonne, 2.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(RefrigerantGasActivity::class, $activity);
        $this->assertSame(3000.0, (float) $activity->emissions()->co2eq());
    }

    public function testRejectsNonMassUnit(): void
    {
        $sut = new RegisterRefrigerantGasActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->expectException(IncompatibleUnitsException::class);
        $sut(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 2.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());
    }

    public function testThrowsWhenGasHasNoFactorForYear(): void
    {
        $sut = new RegisterRefrigerantGasActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->expectException(\InvalidArgumentException::class);
        $sut(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 2.0),
            year: 2030,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = new RegisterRefrigerantGasActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->expectException(\Throwable::class);
        $sut(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: RefrigerantGasId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = new RegisterRefrigerantGasActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->assertTrue($sut->isHandled(new RegisterRefrigerantGasActivityRequest(
            accountId: new AccountId('account-id'),
            refrigerantGasId: RefrigerantGasId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
