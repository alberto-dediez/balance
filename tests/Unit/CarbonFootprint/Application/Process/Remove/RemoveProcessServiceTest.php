<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\Remove;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Application\Process\Remove\RemoveProcessRequest;
use App\CarbonFootprint\Application\Process\Remove\RemoveProcessService;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRemoved;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveProcessServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $process = ProcessMother::registeredProcess();

        $repository->add($process);

        $request = new RemoveProcessRequest(
            processId: $process->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($process->id()->equals($result->processId));

        $processPersisted = $repository->find($result->processId);

        $this->assertNull($processPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProcessRemoved);
    }

    public function testThrowsWhenNotFound(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemoveProcessRequest(processId: ProcessId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = ProcessRepositoryMother::inMemory();
        $process = ProcessMother::random();
        $repository->add($process);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveProcessRequest(processId: $process->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $process = ProcessMother::random();

        $result = $sut->isHandled(new RemoveProcessRequest(
            processId: $process->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProcessRepository $repository): RemoveProcessService
    {
        return new RemoveProcessService($repository, new ProcessAuthorizer());
    }
}
