<?php

namespace App\Tests\Unit\Waste\Domain\Event\Container;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Waste\Domain\Event\Container\RemoveWasteSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class RemoveWasteSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfWasteRemovedEventIsPassed()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $sut = $this->createSUT($repository);

        $event = new WasteRemoved(
            WasteId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseIfNotWasteRemovedEventIsPassed()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $sut = $this->createSUT($repository);

        $event = new ContainerRemoved(
            ContainerId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleCallsRemoveContainerService()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $property = PropertyMother::fromName('Hotel');

        $waste = WasteMother::fromName('Plastico');
        $otherWaste = WasteMother::fromName('Madera');

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $otherWaste->id());
        $repository->add($container);

        $event = new WasteRemoved(
            $waste->id()
        );

        $sut = $this->createSUT(
            repository: $repository, 
            containersToRemoved: 3
        );
        $sut->handle($event);
    }

    private function createSUT($repository, int $containersToRemoved = 0)
    {
        $removeContainer = $this->createMock(RemoveContainerService::class);
        $removeContainer->expects($this->exactly($containersToRemoved))->method('__invoke');
        
        return new RemoveWasteSubscriber(
            $repository,
            $removeContainer
        );
    }
}