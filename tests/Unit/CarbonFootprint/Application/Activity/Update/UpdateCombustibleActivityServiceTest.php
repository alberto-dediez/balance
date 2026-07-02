<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivityUpdated;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
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
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CombustibleEmissionFactorSelector($entityRepo);
        $sut = new UpdateCombustibleActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());

        $sut(new UpdateCombustibleActivityRequest(
            activityId: $activity->id(),
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 300.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(CombustibleActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = CombustibleActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new UpdateCombustibleActivityService($repository, new ActivityAuthorizer(), $selector, new EmissionsCalculator());
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
        $sut = new UpdateCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new UpdateCombustibleActivityRequest(
            activityId: \App\CarbonFootprint\Domain\Model\Activity\ActivityId::generate(),
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
