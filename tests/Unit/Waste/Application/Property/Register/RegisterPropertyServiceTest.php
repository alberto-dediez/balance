<?php

namespace App\Tests\Unit\Waste\Application\Property\Register;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Property\Property;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Property\PropertyRegistered;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Property\Register\RegisterPropertyRequest;
use App\Waste\Application\Property\Register\RegisterPropertyService;
use App\Waste\Application\Property\Register\RegisterPropertyResponse;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;

class RegisterPropertyServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterPropertyRequest(
            name: 'Hotel Abaco'
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterPropertyResponse);
        $this->assertNotEmpty((string) $result->propertyId);
        $this->assertEquals(36, strlen((string) $result->propertyId));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertTrue($propertyPersisted instanceof Property);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyRegistered);
        $this->assertEquals('Hotel Abaco', $lastEvent->name());
        $this->assertTrue($propertyPersisted->id()->equals($lastEvent->propertyId()));
    }

    private function createSUT($repository)
    {
        return new RegisterPropertyService($repository);
    }
}