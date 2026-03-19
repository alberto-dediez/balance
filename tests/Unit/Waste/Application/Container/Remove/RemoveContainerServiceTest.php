<?php

namespace App\Tests\Unit\Waste\Application\Container\Remove;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Application\Container\Remove\RemoveContainerResponse;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;

class RemoveContainerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $container = ContainerMother::registeredContainer();

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $container->accountId());

        $repository->add($container);

        $request = new RemoveContainerRequest(
            containerId: $container->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveContainerResponse);
        $this->assertTrue($container->id()->equals($result->containerId));
        
        $containerPersisted = $repository->find($result->containerId);

        $this->assertNull($containerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $container = ContainerMother::random();

        $result = $sut->isHandled(new RemoveContainerRequest(
            containerId: $container->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryContainerRepository $repository): RemoveContainerService
    {
        return new RemoveContainerService($repository, new ContainerAuthorizer());
    }
}