<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\EconomicSectorAnswerHandler;
use App\CarbonFootprint\Application\Activity\EconomicSectorEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivity;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityUpdated;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
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

class EconomicSectorAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private EconomicSector $economicSector;

    private InMemoryActivityRepository $activityRepository;

    private EconomicSectorAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->economicSector = EconomicSectorMother::registeredEconomicSector();
        $this->economicSector->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        $economicSectorRepository = EconomicSectorRepositoryMother::inMemory();
        $economicSectorRepository->add($this->economicSector);

        $selector = new EconomicSectorEmissionFactorSelector($economicSectorRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new EconomicSectorAnswerHandler(
            new RegisterEconomicSectorActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateEconomicSectorActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsEconomicSector(): void
    {
        $this->assertSame(ActivityType::EconomicSector, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new EconomicSectorAnswer(
                economicSectorId: $this->economicSector->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 100.0),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(EconomicSectorActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertTrue($this->economicSector->id()->equals($activity->economicSectorId()));
    }

    public function testUpdateDelegates(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new EconomicSectorAnswer(
                economicSectorId: $this->economicSector->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 500.0),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(EconomicSectorActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(500.0, $activity->quantity()->value());
    }
}
