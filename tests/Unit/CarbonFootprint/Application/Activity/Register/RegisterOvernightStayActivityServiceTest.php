<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivityRegistered;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterOvernightStayActivityServiceTest extends TestCase
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

        $entity = OvernightStayMother::registeredOvernightStay();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion));
        $entityRepo = OvernightStayRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new OvernightStayEmissionFactorSelector($entityRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterOvernightStayActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterOvernightStayActivityRequest(
            accountId: new AccountId('account-id'),
            overnightStayId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion, 2.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(OvernightStayActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(OvernightStayActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new OvernightStayEmissionFactorSelector(OvernightStayRepositoryMother::inMemory());
        $sut = new RegisterOvernightStayActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterOvernightStayActivityRequest(
            accountId: new AccountId('account-id'),
            overnightStayId: OvernightStayId::generate(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new OvernightStayEmissionFactorSelector(OvernightStayRepositoryMother::inMemory());
        $sut = new RegisterOvernightStayActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterOvernightStayActivityRequest(
            accountId: new AccountId('account-id'),
            overnightStayId: OvernightStayId::generate(),
            quantity: ActivityMother::quantity(Unit::Pernoctacion),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
