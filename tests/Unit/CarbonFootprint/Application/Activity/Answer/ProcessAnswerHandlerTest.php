<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ProcessAnswerHandler;
use App\CarbonFootprint\Application\Activity\ProcessEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterProcessActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateProcessActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ProcessAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivity;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityUpdated;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ProcessActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ProcessAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private Process $process;

    private InMemoryActivityRepository $activityRepository;

    private ProcessAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->process = ProcessMother::registeredProcess();
        $this->process->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));

        $processRepository = ProcessRepositoryMother::inMemory();
        $processRepository->add($this->process);

        $selector = new ProcessEmissionFactorSelector($processRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new ProcessAnswerHandler(
            new RegisterProcessActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateProcessActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsProcess(): void
    {
        $this->assertSame(ActivityType::Process, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new ProcessAnswer(
                processId: $this->process->id(),
                quantity: ActivityMother::quantity(Unit::Piece, 100.0),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(ProcessActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertTrue($this->process->id()->equals($activity->processId()));
    }

    public function testUpdateDelegates(): void
    {
        $activity = ProcessActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new ProcessAnswer(
                processId: $this->process->id(),
                quantity: ActivityMother::quantity(Unit::Piece, 500.0),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(ProcessActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(500.0, $activity->quantity()->value());
    }
}
