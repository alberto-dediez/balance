<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\UseProfileAnswerHandler;
use App\CarbonFootprint\Application\Activity\Register\RegisterUseProfileActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateUseProfileActivityService;
use App\CarbonFootprint\Application\Activity\UseProfileEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\UseProfileAnswer;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivity;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
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

class UseProfileAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private UseProfile $useProfile;

    private InMemoryActivityRepository $activityRepository;

    private UseProfileAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->useProfile = UseProfileMother::registeredUseProfile();
        $this->useProfile->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));

        $useProfileRepository = UseProfileRepositoryMother::inMemory();
        $useProfileRepository->add($this->useProfile);

        $selector = new UseProfileEmissionFactorSelector($useProfileRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new UseProfileAnswerHandler(
            new RegisterUseProfileActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateUseProfileActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsUseProfile(): void
    {
        $this->assertSame(ActivityType::UseProfile, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new UseProfileAnswer(
                useProfileId: $this->useProfile->id(),
                quantity: ActivityMother::quantity(Unit::Piece, 100.0),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(UseProfileActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertTrue($this->useProfile->id()->equals($activity->useProfileId()));
    }

    public function testUpdateDelegates(): void
    {
        $activity = UseProfileActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new UseProfileAnswer(
                useProfileId: $this->useProfile->id(),
                quantity: ActivityMother::quantity(Unit::Piece, 500.0),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(UseProfileActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(500.0, $activity->quantity()->value());
    }
}
