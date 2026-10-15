<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\Update\UpdateUseProfileActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateUseProfileActivityService;
use App\CarbonFootprint\Application\Activity\UseProfileEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\UseProfileActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateUseProfileActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRecalculatesEmissions(): void
    {
        $activity = UseProfileActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $useProfile = UseProfileMother::registeredUseProfile();
        $useProfile->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));
        $useProfileRepository = UseProfileRepositoryMother::inMemory();
        $useProfileRepository->add($useProfile);

        $sut = $this->createSUT($activityRepository, $useProfileRepository);

        $sut(
            $this->request($activity->id(), $useProfile->id(), 50.0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(UseProfileActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($useProfile->id()->equals($activity->useProfileId()));
        $this->assertEqualsWithDelta(75.0, (float) $activity->emissions()->co2eq(), 1e-9);
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), UseProfileRepositoryMother::inMemory());

        $this->expectException(ActivityNotFoundException::class);

        $sut($this->request(ActivityId::generate(), UseProfileId::generate(), 50.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = UseProfileActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $sut = $this->createSUT($activityRepository, UseProfileRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request($activity->id(), UseProfileId::generate(), 50.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), UseProfileRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ActivityId::generate(), UseProfileId::generate(), 50.0)));
    }

    private function request(ActivityId $activityId, UseProfileId $useProfileId, float $quantity): UpdateUseProfileActivityRequest
    {
        return new UpdateUseProfileActivityRequest(
            activityId: $activityId,
            useProfileId: $useProfileId,
            quantity: ActivityMother::quantity(Unit::Piece, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, UseProfileRepository $useProfileRepository): UpdateUseProfileActivityService
    {
        return new UpdateUseProfileActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new UseProfileEmissionFactorSelector($useProfileRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
