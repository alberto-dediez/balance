<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityService;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivityRegistered;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterTransportActivityServiceTest extends TestCase
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

        $entity = TransportMother::registeredTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new TransportEmissionFactorSelector($entityRepo);
        $calculator = EmissionsCalculatorMother::create();
        $sut = new RegisterTransportActivityService($activityRepo, new ActivityAuthorizer(), $selector, $calculator);

        $result = $sut(new RegisterTransportActivityRequest(
            accountId: new AccountId('account-id'),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 500.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());

        $this->assertInstanceOf(TransportActivity::class, $activityRepo->findById($result->activityId));
        $this->assertInstanceOf(TransportActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisterInTonneKilometreKeepsWeightAndDistance(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $entity = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new RegisterTransportActivityService($activityRepo, new ActivityAuthorizer(), new TransportEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $result = $sut(new RegisterTransportActivityRequest(
            accountId: new AccountId('account-id'),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::TonneKilometre, 875.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            weight: new Quantity(2500.0, Unit::Kilogram),
            distanceKm: 350.0,
        ), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);
        $this->assertInstanceOf(TransportActivity::class, $activity);
        $this->assertSame('1312.5', $activity->emissions()->co2eq());
        $weight = $activity->weight();
        $this->assertNotNull($weight);
        $this->assertSame(2500.0, $weight->value());
        $this->assertSame(Unit::Kilogram, $weight->unit());
        $this->assertSame(350.0, $activity->distanceKm());
    }

    public function testRegisterInKilogramsForTonneKilometreTransportThrows(): void
    {
        $entity = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new RegisterTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), new TransportEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $this->expectException(IncompatibleUnitsException::class);

        $sut(new RegisterTransportActivityRequest(
            accountId: new AccountId('account-id'),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 2500.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $selector = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());
        $sut = new RegisterTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);
        $sut(new RegisterTransportActivityRequest(
            accountId: new AccountId('account-id'),
            transportId: TransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());
        $sut = new RegisterTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new RegisterTransportActivityRequest(
            accountId: new AccountId('account-id'),
            transportId: TransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
