<?php

namespace App\Tests\Unit\Waste\Domain\Event\Container;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use PHPUnit\Framework\TestCase;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Waste\Domain\Event\Container\RemovePropertySubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;

class RemovePropertySubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfPropertyRemovedEventIsPassed(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new PropertyRemoved(
            PropertyId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseIfNotPropertyRemovedEventIsPassed(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new ContainerRemoved(
            ContainerId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleCallsRemoveContainerService(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $property = PropertyMother::fromName('Hotel');
        $otherProperty = PropertyMother::fromName('OtherHotel');

        $waste = WasteMother::fromName('Plastico');

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id(), $waste->id());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($otherProperty->id(), $waste->id());
        $repository->add($container);

        $event = new PropertyRemoved(
            $property->id()
        );

        $sut = $this->createSUT(
            repository: $repository, 
            containersToRemoved: 3
        );
        $sut->handle($event);
    }

    private function createSUT(InMemoryContainerRepository $repository, int $containersToRemoved = 0): RemovePropertySubscriber
    {
        $removeContainer = $this->createMock(RemoveContainerService::class);
        $removeContainer->expects($this->exactly($containersToRemoved))->method('__invoke');
        
        /** @var RemoveContainerService $removeContainer **/

        $loggedUser = new FakeLoggedUser(UserMother::random());

        return new RemovePropertySubscriber(
            $repository,
            $removeContainer,
            $loggedUser
        );
    }
}