<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\CapitalGoodEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateCapitalGoodActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCapitalGoodActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityUpdated;
use App\CarbonFootprint\Domain\Model\Activity\InvalidAcquisitionYearException;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateCapitalGoodActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRecalculatesEmissions(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2019);
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $capitalGood = CapitalGoodMother::registeredCapitalGood();
        $capitalGood->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));
        $capitalGoodRepository = CapitalGoodRepositoryMother::inMemory();
        $capitalGoodRepository->add($capitalGood);

        $sut = $this->createSUT($activityRepository, $capitalGoodRepository);

        $sut(
            $this->request($activity->id(), $capitalGood->id(), 50.0, 2021),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(CapitalGoodActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($capitalGood->id()->equals($activity->capitalGoodId()));
        $this->assertEqualsWithDelta(75.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertSame(2021, $activity->acquisitionYear());
    }

    public function testThrowsWhenAcquisitionYearIsOutOfRange(): void
    {
        $activity = CapitalGoodActivityMother::registered(acquisitionYear: 2019);
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $capitalGood = CapitalGoodMother::registeredCapitalGood();
        $capitalGood->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));
        $capitalGoodRepository = CapitalGoodRepositoryMother::inMemory();
        $capitalGoodRepository->add($capitalGood);

        $sut = $this->createSUT($activityRepository, $capitalGoodRepository);

        $this->expectException(InvalidAcquisitionYearException::class);

        $sut(
            $this->request($activity->id(), $capitalGood->id(), 50.0, 1899),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), CapitalGoodRepositoryMother::inMemory());

        $this->expectException(ActivityNotFoundException::class);

        $sut($this->request(ActivityId::generate(), CapitalGoodId::generate(), 50.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = CapitalGoodActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $sut = $this->createSUT($activityRepository, CapitalGoodRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request($activity->id(), CapitalGoodId::generate(), 50.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), CapitalGoodRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ActivityId::generate(), CapitalGoodId::generate(), 50.0)));
    }

    private function request(ActivityId $activityId, CapitalGoodId $capitalGoodId, float $quantity, ?int $acquisitionYear = null): UpdateCapitalGoodActivityRequest
    {
        return new UpdateCapitalGoodActivityRequest(
            activityId: $activityId,
            capitalGoodId: $capitalGoodId,
            quantity: ActivityMother::quantity(Unit::Euro, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            acquisitionYear: $acquisitionYear,
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, CapitalGoodRepository $capitalGoodRepository): UpdateCapitalGoodActivityService
    {
        return new UpdateCapitalGoodActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new CapitalGoodEmissionFactorSelector($capitalGoodRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
