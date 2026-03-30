<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Event\Container;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Application\Container\Remove\RemoveContainerService;
use App\Waste\Domain\Event\Container\RemoveWasteSubscriber;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRemoved;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveWasteSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfWasteRemovedEventIsPassed(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new WasteRemoved(
            WasteId::generate(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseIfNotWasteRemovedEventIsPassed(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new ContainerRemoved(
            ContainerId::generate(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleCallsRemoveContainerService(): void
    {
        $repository = ContainerRepositoryMother::inMemory();

        $property = PropertyMother::fromName('Hotel');

        $waste = WasteMother::fromName('Plastico');
        $otherWaste = WasteMother::fromName('Madera');

        $container = ContainerMother::fromPropertyAndWaste($property->id()->__toString(), $waste->id()->__toString());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id()->__toString(), $waste->id()->__toString());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id()->__toString(), $waste->id()->__toString());
        $repository->add($container);

        $container = ContainerMother::fromPropertyAndWaste($property->id()->__toString(), $otherWaste->id()->__toString());
        $repository->add($container);

        $event = new WasteRemoved(
            $waste->id(),
        );

        $sut = $this->createSUT(
            repository: $repository,
            containersToRemoved: 3,
        );
        $sut->handle($event);
    }

    private function createSUT(InMemoryContainerRepository $repository, int $containersToRemoved = 0): RemoveWasteSubscriber
    {

        /** @var RemoveContainerService&MockObject $removeContainer */
        $removeContainer = $this->createMock(RemoveContainerService::class);
        $removeContainer->expects($this->exactly($containersToRemoved))->method('__invoke');


        $user = UserMother::random();

        $loggedUser = new FakeLoggedUser($user);

        return new RemoveWasteSubscriber(
            $repository,
            $removeContainer,
            $loggedUser,
        );
    }
}
