<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivityRegistered;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterElectricityActivityServiceTest extends TestCase
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

        $marketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $marketer->setEmissionFactor(ActivityMother::emissionFactor());
        $marketerRepo = ElectricityMarketerRepositoryMother::inMemory();
        $marketerRepo->add($marketer);

        $gdoFactor = GdOFactorMother::registeredGdOFactor();
        $gdoFactor->setEmissionFactor(ActivityMother::emissionFactor());
        $gdoFactorRepo = GdOFactorRepositoryMother::inMemory();
        $gdoFactorRepo->add($gdoFactor);

        $selector = new ElectricityEmissionFactorSelector($marketerRepo, $gdoFactorRepo);
        $calculator = new EmissionsCalculator();
        $sut = new RegisterElectricityActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: $marketer->id(),
            gdOFactorId: $gdoFactor->id(),
            quantity: ActivityMother::quantity(Unit::KWh, 1000.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(ElectricityActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(ElectricityActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new RegisterElectricityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->expectException(\Throwable::class);
        $sut(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new ElectricityEmissionFactorSelector(
            ElectricityMarketerRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
        $sut = new RegisterElectricityActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, new EmissionsCalculator());
        $this->assertTrue($sut->isHandled(new RegisterElectricityActivityRequest(
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: GdOFactorId::generate(),
            quantity: ActivityMother::quantity(Unit::KWh),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
