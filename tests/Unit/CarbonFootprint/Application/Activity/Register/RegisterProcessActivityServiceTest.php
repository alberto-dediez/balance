<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ProcessEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterProcessActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterProcessActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivity;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityRegistered;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Process\Process;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterProcessActivityServiceTest extends TestCase
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
        $process = $this->processWithFactor();
        $sut = $this->createSUT($activityRepository, $this->repositoryWith($process));

        $result = $sut($this->request($process->id(), Unit::Piece, 100.0), UserMother::adminUser());

        $activity = $activityRepository->findById($result->activityId);

        $this->assertInstanceOf(ProcessActivity::class, $activity);
        $this->assertTrue($process->id()->equals($activity->processId()));
        $this->assertEqualsWithDelta(150.0, (float) $activity->emissions()->co2eq(), 1e-9);
        $this->assertInstanceOf(ProcessActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testThrowsWhenQuantityMagnitudeDiffersFromFactor(): void
    {
        $process = $this->processWithFactor();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($process));

        $this->expectException(IncompatibleUnitsException::class);

        $sut($this->request($process->id(), Unit::Kilogram, 100.0), UserMother::adminUser());
    }

    public function testThrowsWhenNoFactorForYear(): void
    {
        $process = ProcessMother::registeredProcess();
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), $this->repositoryWith($process));

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($process->id(), Unit::Piece, 100.0), UserMother::adminUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), ProcessRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(ProcessId::generate(), Unit::Piece, 100.0), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ActivityRepositoryMother::inMemory(), ProcessRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled($this->request(ProcessId::generate(), Unit::Piece, 100.0)));
    }

    private function processWithFactor(): Process
    {
        $process = ProcessMother::registeredProcess();
        $process->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Piece));

        return $process;
    }

    private function repositoryWith(Process $process): ProcessRepository
    {
        $repository = ProcessRepositoryMother::inMemory();
        $repository->add($process);

        return $repository;
    }

    private function request(ProcessId $processId, Unit $unit, float $quantity): RegisterProcessActivityRequest
    {
        return new RegisterProcessActivityRequest(
            accountId: new AccountId('account-id'),
            processId: $processId,
            quantity: ActivityMother::quantity($unit, $quantity),
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }

    private function createSUT(InMemoryActivityRepository $activityRepository, ProcessRepository $processRepository): RegisterProcessActivityService
    {
        return new RegisterProcessActivityService(
            $activityRepository,
            new ActivityAuthorizer(),
            new ProcessEmissionFactorSelector($processRepository),
            EmissionsCalculatorMother::create(),
        );
    }
}
