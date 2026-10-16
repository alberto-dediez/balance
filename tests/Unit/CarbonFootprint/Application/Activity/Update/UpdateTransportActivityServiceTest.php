<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\TransportEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivityUpdated;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\TransportActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateTransportActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = TransportActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $entity = TransportMother::registeredTransport();
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $selector = new TransportEmissionFactorSelector($entityRepo);
        $sut = new UpdateTransportActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());

        $sut(new UpdateTransportActivityRequest(
            activityId: $activity->id(),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilometer, 600.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertInstanceOf(TransportActivityUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testUpdateInTonneKilometreKeepsWeightAndDistance(): void
    {
        $activity = TransportActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $entity = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new UpdateTransportActivityService($repository, new ActivityAuthorizer(), new TransportEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $sut(new UpdateTransportActivityRequest(
            activityId: $activity->id(),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::TonneKilometre, 875.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            weight: new Quantity(2.5, Unit::Tonne),
            distanceKm: 350.0,
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $updated = $repository->findById($activity->id());
        $this->assertInstanceOf(TransportActivity::class, $updated);
        $this->assertSame('1312.5', $updated->emissions()->co2eq());
        $weight = $updated->weight();
        $this->assertNotNull($weight);
        $this->assertSame(2.5, $weight->value());
        $this->assertSame(Unit::Tonne, $weight->unit());
        $this->assertSame(350.0, $updated->distanceKm());
    }

    public function testUpdateInKilogramsForTonneKilometreTransportThrows(): void
    {
        $activity = TransportActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $entity = TransportMother::freightTrainInTonneKilometre('Tren', (string) TransportId::generate());
        $entity->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre));
        $entityRepo = TransportRepositoryMother::inMemory();
        $entityRepo->add($entity);
        $sut = new UpdateTransportActivityService($repository, new ActivityAuthorizer(), new TransportEmissionFactorSelector($entityRepo), EmissionsCalculatorMother::create());

        $this->expectException(IncompatibleUnitsException::class);

        $sut(new UpdateTransportActivityRequest(
            activityId: $activity->id(),
            transportId: $entity->id(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 2500.0),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = TransportActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $selector = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());
        $sut = new UpdateTransportActivityService($repository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->expectException(\Throwable::class);

        $sut(new UpdateTransportActivityRequest(
            activityId: $activity->id(),
            transportId: TransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $selector = new TransportEmissionFactorSelector(TransportRepositoryMother::inMemory());
        $sut = new UpdateTransportActivityService(ActivityRepositoryMother::inMemory(), new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create());
        $this->assertTrue($sut->isHandled(new UpdateTransportActivityRequest(
            activityId: ActivityId::generate(),
            transportId: TransportId::generate(),
            quantity: ActivityMother::quantity(Unit::Kilometer),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        )));
    }
}
