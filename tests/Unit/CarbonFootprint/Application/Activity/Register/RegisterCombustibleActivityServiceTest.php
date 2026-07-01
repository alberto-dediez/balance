<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CombustibleEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivityRegistered;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
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
        $entity->setEmissionFactor(ActivityMother::emissionFactor());
        $entityRepo = CombustibleRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new CombustibleEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterCombustibleActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterCombustibleActivityRequest(
            combustibleId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Liter, 200.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(CombustibleActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(CombustibleActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new RegisterCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterCombustibleActivityRequest(
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new CombustibleEmissionFactorSelector(CombustibleRepositoryMother::inMemory());
        $sut = new RegisterCombustibleActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterCombustibleActivityRequest(
            combustibleId: CombustibleId::generate(),
            quantity: ActivityMother::quantity(Unit::Liter),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
        )));
    }
}
