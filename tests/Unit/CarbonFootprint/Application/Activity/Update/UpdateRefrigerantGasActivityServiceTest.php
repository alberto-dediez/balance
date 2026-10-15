<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityUpdated;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateRefrigerantGasActivityServiceTest extends TestCase
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
        $activity = RefrigerantGasActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut = new UpdateRefrigerantGasActivityService($repository, new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $sut(new UpdateRefrigerantGasActivityRequest(
            activityId: $activity->id(),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 3.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(RefrigerantGasActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(4.5, (float) $activity->emissions()->co2eq());
    }

    public function testUnauthorizedAccessFromAnotherAccount(): void
    {
        $activity = RefrigerantGasActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $sut = new UpdateRefrigerantGasActivityService($repository, new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->expectException(\Throwable::class);
        $sut(new UpdateRefrigerantGasActivityRequest(
            activityId: $activity->id(),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 3.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account')));
    }

    public function testThrowsWhenActivityIsNotARefrigerantGasActivity(): void
    {
        $activity = CombustibleActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $sut = new UpdateRefrigerantGasActivityService($repository, new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->expectException(ActivityNotFoundException::class);
        $sut(new UpdateRefrigerantGasActivityRequest(
            activityId: $activity->id(),
            refrigerantGasId: $this->refrigerantGas->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 3.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));
    }

    public function testIsHandled(): void
    {
        $sut = new UpdateRefrigerantGasActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $this->selector, EmissionsCalculatorMother::create());

        $this->assertTrue($sut->isHandled(new UpdateRefrigerantGasActivityRequest(
            activityId: ActivityId::generate(),
            refrigerantGasId: RefrigerantGasId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilogram),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
