<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\Remove;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\Remove\RemovePropertyRequest;
use App\Property\Application\Property\Remove\RemovePropertyResponse;
use App\Property\Application\Property\Remove\RemovePropertyService;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemovePropertyServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $request = new RemovePropertyRequest(
            propertyId: $property->id(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemovePropertyResponse);
        $this->assertTrue($property->id()->equals($result->propertyId));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNull($propertyPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $property = PropertyMother::random();

        $result = $sut->isHandled(new RemovePropertyRequest(
            propertyId: $property->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryPropertyRepository $repository): RemovePropertyService
    {
        return new RemovePropertyService($repository, new PropertyAuthorizer());
    }
}
