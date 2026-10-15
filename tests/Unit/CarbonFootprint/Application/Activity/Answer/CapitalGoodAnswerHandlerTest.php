<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\CapitalGoodAnswerHandler;
use App\CarbonFootprint\Application\Activity\CapitalGoodEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCapitalGoodActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCapitalGoodActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CapitalGoodAnswer;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivity;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityUpdated;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
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

class CapitalGoodAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private CapitalGood $capitalGood;

    private InMemoryActivityRepository $activityRepository;

    private CapitalGoodAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->capitalGood = CapitalGoodMother::registeredCapitalGood();
        $this->capitalGood->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        $capitalGoodRepository = CapitalGoodRepositoryMother::inMemory();
        $capitalGoodRepository->add($this->capitalGood);

        $selector = new CapitalGoodEmissionFactorSelector($capitalGoodRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new CapitalGoodAnswerHandler(
            new RegisterCapitalGoodActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateCapitalGoodActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsCapitalGood(): void
    {
        $this->assertSame(ActivityType::CapitalGood, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new CapitalGoodAnswer(
                capitalGoodId: $this->capitalGood->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 100.0),
                acquisitionYear: 2023,
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(CapitalGoodActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertTrue($this->capitalGood->id()->equals($activity->capitalGoodId()));
        $this->assertSame(2023, $activity->acquisitionYear());
    }

    public function testUpdateDelegates(): void
    {
        $activity = CapitalGoodActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new CapitalGoodAnswer(
                capitalGoodId: $this->capitalGood->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 500.0),
                existingActivityId: $activity->id(),
                acquisitionYear: 2018,
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(CapitalGoodActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(500.0, $activity->quantity()->value());
        $this->assertSame(2018, $activity->acquisitionYear());
    }
}
