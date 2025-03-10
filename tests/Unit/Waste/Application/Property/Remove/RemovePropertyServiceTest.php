<?php

namespace App\Tests\Unit\Waste\Application\Property\Remove;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Waste\Application\Property\Remove\RemovePropertyRequest;
use App\Waste\Application\Property\Remove\RemovePropertyService;
use App\Waste\Application\Property\Remove\RemovePropertyResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;

class RemovePropertyServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = new InMemoryPropertyRepository();

        $sut = $this->createSUT($repository);

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $request = new RemovePropertyRequest(
            propertyId: $property->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemovePropertyResponse);
        $this->assertTrue($property->id()->equals($result->propertyId));
        
        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNull($propertyPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyRemoved);
    }

    private function createSUT($repository)
    {
        return new RemovePropertyService($repository);
    }
}