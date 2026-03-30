<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Destination\Remove;

use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Application\Destination\Remove\RemoveDestinationRequest;
use App\Property\Application\Destination\Remove\RemoveDestinationService;
use App\Property\Domain\Model\Destination\DestinationRemoved;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveDestinationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $destination = DestinationMother::random();

        $repository->add($destination);

        $request = new RemoveDestinationRequest(
            destinationId: $destination->id(),
        );

        $result = $sut($request, $user);

        $this->assertTrue($destination->id()->equals($result->destinationId));

        $destinationPersisted = $repository->find($result->destinationId);

        $this->assertNull($destinationPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DestinationRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $destination = DestinationMother::random();

        $result = $sut->isHandled(new RemoveDestinationRequest(
            destinationId: $destination->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryDestinationRepository $repository): RemoveDestinationService
    {
        return new RemoveDestinationService($repository, new DestinationAuthorizer());
    }
}
