<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\ProcessEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateProcessActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateProcessActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityUpdated;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Application\UnauthorizedActionException;
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

class UpdateProcessActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRecalculatesEmissions(): void
    {
        $activity = ProcessActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $process = ProcessMother::registeredProcess();
        $process->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));
        $processRepository = ProcessRepositoryMother::inMemory();
        $processRepository->add($process);

        $sut = $this->createSUT($activityRepository, $processRepository);

        $sut(
            $this->request($activity->id(), $process->id(), 50.0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(ProcessActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($process->id()->equals($activity->processId()));
        $this->assertEqualsWithDelta(75.0, (float) $activity->emissions()->co2eq(), 1e-9);
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), ProcessRepositoryMother::inMemory());

        $this->expectException(ActivityNotFoundException::class);

        $sut($this->request(ActivityId::generate(), ProcessId::generate(), 50.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = ProcessActivityMother::registered();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $sut = $this->createSUT($activityRepository, ProcessRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request($activity->id(), ProcessId::generate(), 50.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), ProcessRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ActivityId::generate(), ProcessId::generate(), 50.0)));
    }

    private function request(ActivityId $activityId, ProcessId $processId, float $quantity): UpdateProcessActivityRequest
    {
        return new UpdateProcessActivityRequest(
            activityId: $activityId,
            processId: $processId,
            quantity: ActivityMother::quantity(Unit::Piece, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, ProcessRepository $processRepository): UpdateProcessActivityService
    {
        return new UpdateProcessActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new ProcessEmissionFactorSelector($processRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
