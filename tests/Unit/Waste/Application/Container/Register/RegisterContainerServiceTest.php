<?php

namespace App\Tests\Unit\Waste\Application\Container\Register;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\ContainerRegistered;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Container\Register\RegisterContainerRequest;
use App\Waste\Application\Container\Register\RegisterContainerService;
use App\Waste\Application\Container\Register\RegisterContainerResponse;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class RegisterContainerServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $sut = $this->createSUT($repository);

        $request = new RegisterContainerRequest(
            propertyId: new PropertyId('123'),
            wasteId: new WasteId('444'),
            purpose: Purpose::Recycling,
            measurementMethod: MeasurementMethod::Weight
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterContainerResponse);
        $this->assertNotEmpty((string) $result->containerId);
        $this->assertEquals(36, strlen((string) $result->containerId));

        $containerPersisted = $repository->find($result->containerId);

        $this->assertNotNull($containerPersisted);
        $this->assertTrue($containerPersisted instanceof Container);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerRegistered);
        $this->assertTrue($containerPersisted->id()->equals($lastEvent->containerId()));
    }

    private function createSUT($repository)
    {
        return new RegisterContainerService(
            containerRepository: $repository
        );
    }
}