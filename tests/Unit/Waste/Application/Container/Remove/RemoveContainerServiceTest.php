<?php

namespace App\Tests\Unit\Waste\Application\Container\Remove;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Application\Container\Remove\RemoveContainerResponse;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class RemoveContainerServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $sut = $this->createSUT($repository);

        $container = ContainerMother::registeredContainer();

        $repository->add($container);

        $request = new RemoveContainerRequest(
            containerId: $container->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveContainerResponse);
        $this->assertTrue($container->id()->equals($result->containerId));
        
        $containerPersisted = $repository->find($result->containerId);

        $this->assertNull($containerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveContainerService($repository);
    }
}