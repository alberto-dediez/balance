<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityUpdated;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateEconomicSectorActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRecalculatesEmissions(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $economicSector = EconomicSectorMother::registeredEconomicSector();
        $economicSector->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));
        $economicSectorRepository = EconomicSectorRepositoryMother::inMemory();
        $economicSectorRepository->add($economicSector);

        $sut = $this->createSUT($activityRepository, $economicSectorRepository);

        $sut(
            $this->request($activity->id(), $economicSector->id(), 50.0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(EconomicSectorActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($economicSector->id()->equals($activity->economicSectorId()));
        $this->assertEqualsWithDelta(75.0, (float) $activity->emissions()->co2eq(), 1e-9);
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), EconomicSectorRepositoryMother::inMemory());

        $this->expectException(ActivityNotFoundException::class);

        $sut($this->request(ActivityId::generate(), EconomicSectorId::generate(), 50.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $sut = $this->createSUT($activityRepository, EconomicSectorRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request($activity->id(), EconomicSectorId::generate(), 50.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), EconomicSectorRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ActivityId::generate(), EconomicSectorId::generate(), 50.0)));
    }

    private function request(ActivityId $activityId, EconomicSectorId $economicSectorId, float $quantity): UpdateEconomicSectorActivityRequest
    {
        return new UpdateEconomicSectorActivityRequest(
            activityId: $activityId,
            economicSectorId: $economicSectorId,
            quantity: ActivityMother::quantity(Unit::Euro, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, EconomicSectorRepository $economicSectorRepository): UpdateEconomicSectorActivityService
    {
        return new UpdateEconomicSectorActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new EconomicSectorEmissionFactorSelector($economicSectorRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
