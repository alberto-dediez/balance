<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Register\RegisterUseProfileActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterUseProfileActivityService;
use App\CarbonFootprint\Application\Activity\UseProfileEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivity;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityRegistered;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterUseProfileActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activityRepository = ActivityRepositoryMother::inMemory();
        $useProfile = $this->useProfileWithFactor();
        $sut = $this->createSUT($activityRepository, $this->repositoryWith($useProfile));

        $result = $sut($this->request($useProfile->id(), Unit::Piece, 100.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(UseProfileActivity::class, $activity);
        $this->assertTrue($useProfile->id()->equals($activity->useProfileId()));
        $this->assertEqualsWithDelta(150.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertInstanceOf(UseProfileActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testThrowsWhenQuantityMagnitudeDiffersFromFactor(): void
    {
        $useProfile = $this->useProfileWithFactor();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($useProfile));

        $this->expectException(IncompatibleUnitsException::class);

        $sut($this->request($useProfile->id(), Unit::Kilogram, 100.0), UserMother::adminUser());
    }

    public function testThrowsWhenNoFactorForYear(): void
    {
        $useProfile = UseProfileMother::registeredUseProfile();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($useProfile));

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($useProfile->id(), Unit::Piece, 100.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), UseProfileRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(UseProfileId::generate(), Unit::Piece, 100.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), UseProfileRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(UseProfileId::generate(), Unit::Piece, 100.0)));
    }

    private function useProfileWithFactor(): UseProfile
    {
        $useProfile = UseProfileMother::registeredUseProfile();
        $useProfile->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));

        return $useProfile;
    }

    private function repositoryWith(UseProfile $useProfile): UseProfileRepository
    {
        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add($useProfile);

        return $repository;
    }

    private function request(UseProfileId $useProfileId, Unit $unit, float $quantity): RegisterUseProfileActivityRequest
    {
        return new RegisterUseProfileActivityRequest(
            accountId: new AccountId('account-id'),
            useProfileId: $useProfileId,
            quantity: ActivityMother::quantity($unit, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, UseProfileRepository $useProfileRepository): RegisterUseProfileActivityService
    {
        return new RegisterUseProfileActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new UseProfileEmissionFactorSelector($useProfileRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
