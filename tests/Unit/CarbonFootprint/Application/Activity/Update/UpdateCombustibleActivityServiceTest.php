<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivityUpdated;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateCombustibleActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = CombustibleActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter));
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CombustibleEmissionFactorSelector($entityRepo);
        $sut = new UpdateCombustibleActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateCombustibleActivityRequest(
            activityId: $activity->id(),
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 300.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(CombustibleActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = CombustibleActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new UpdateCombustibleActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateCombustibleActivityRequest(
            activityId: $activity->id(),
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new UpdateCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateCombustibleActivityRequest(
            activityId: ActivityId::generate(),
            combustibleId: CombustibleId::generate(),
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

    private function updateWith(?EmissionFactor $wellToTankFactor): CombustibleActivity
    {
        $activity = CombustibleActivityMother::registeredWithWellToTank();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $entity = CombustibleMother::registeredCombustible();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Liter), $wellToTankFactor);
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new UpdateCombustibleActivityService($repository, new ActivityAuthorizer(), new CombustibleEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $sut(new UpdateCombustibleActivityRequest(
            activityId: $activity->id(),
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $updated = $repository->findById($activity->id());
        $this->assertInstanceOf(CombustibleActivity::class, $updated);

        return $updated;
    }
}
