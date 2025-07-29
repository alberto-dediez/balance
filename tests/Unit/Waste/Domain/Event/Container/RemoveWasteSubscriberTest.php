<?php

namespace App\Tests\Unit\Waste\Domain\Event\Container;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Event\Container\RemoveWasteSubscriber;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;

class RemoveWasteSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfWasteRemovedEventIsPassed()
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new WasteRemoved(
            WasteId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseIfNotWasteRemovedEventIsPassed()
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new ContainerRemoved(
            ContainerId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleCallsRemoveContainerService()
    {
        $repository = ContainerRepositoryMother::inMemory();

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

        /** @var RemoveContainerService $removeContainer **/

        $user = UserMother::random();

        $loggedUser = new FakeLoggedUser($user);
        
        return new RemoveWasteSubscriber(
            $repository,
            $removeContainer,
            $loggedUser
        );
    }
}